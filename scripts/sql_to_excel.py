#!/usr/bin/env python3
"""
Convert a MySQL / MariaDB dump file containing INSERT statements into an Excel workbook.

Each table in the dump is exported to a separate worksheet.
"""

from __future__ import annotations

import argparse
from collections import defaultdict
from collections.abc import Iterable, Iterator
import pathlib
import re
from typing import Dict, List, Optional, Tuple

import pandas as pd


CREATE_TABLE_RE = re.compile(r"CREATE TABLE\s+`(?P<name>[^`]+)`", re.IGNORECASE)
INSERT_HEADER_RE = re.compile(
    r"INSERT\s+INTO\s+`(?P<table>[^`]+)`\s*\((?P<columns>.+?)\)\s*VALUES\s*(?P<values>.*)",
    re.IGNORECASE | re.DOTALL,
)


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Convert a MySQL/MariaDB .sql dump file into an Excel workbook."
    )
    parser.add_argument("input", type=pathlib.Path, help="Path to the SQL dump file")
    parser.add_argument(
        "-o",
        "--output",
        type=pathlib.Path,
        help="Output Excel file. Defaults to the input name with .xlsx extension.",
    )
    return parser.parse_args()


def sanitize_sheet_name(name: str, used: Iterable[str]) -> str:
    invalid_chars = set('[]:*?/\\')
    clean = "".join("_" if ch in invalid_chars else ch for ch in name)
    clean = clean[:31] or "Sheet"

    base = clean
    suffix = 1
    used_set = set(used)
    while clean in used_set:
        suffix += 1
        trimmed = base[: 31 - len(str(suffix)) - 1]
        clean = f"{trimmed}_{suffix}"
    return clean


def unescape_mysql(text: str) -> str:
    replacements = [
        (r"\\0", "\0"),
        (r"\\b", "\b"),
        (r"\\n", "\n"),
        (r"\\r", "\r"),
        (r"\\t", "\t"),
        (r"\\Z", "\x1A"),
        (r"\\'", "'"),
        (r'\\"', '"'),
        (r"\\\\", "\\"),
        ("''", "'"),
    ]
    for source, target in replacements:
        text = text.replace(source, target)
    return text


def convert_scalar(value: str):
    raw = value.strip()
    if raw.upper() == "NULL":
        return None

    if raw.startswith("'") and raw.endswith("'"):
        inner = raw[1:-1]
        return unescape_mysql(inner)

    if raw.startswith("0x"):
        return raw

    try:
        return int(raw)
    except ValueError:
        pass
    try:
        return float(raw)
    except ValueError:
        pass

    return raw


def parse_row(content: str) -> List:
    values: List[str] = []
    current: List[str] = []
    in_string = False
    escape = False
    i = 0
    length = len(content)

    while i < length:
        ch = content[i]

        if in_string:
            current.append(ch)
            if escape:
                escape = False
            elif ch == "\\":
                escape = True
            elif ch == "'":
                in_string = False
            i += 1
            continue

        if ch == "'":
            in_string = True
            escape = False
            current.append(ch)
            i += 1
            continue

        if ch == ",":
            values.append("".join(current).strip())
            current = []
            i += 1
            continue

        current.append(ch)
        i += 1

    if current:
        values.append("".join(current).strip())

    return [convert_scalar(item) for item in values]


def iter_value_groups(values_raw: str) -> Iterator[str]:
    depth = 0
    in_string = False
    escape = False
    current: List[str] = []
    length = len(values_raw)
    i = 0

    while i < length:
        ch = values_raw[i]
        if ch == ";" and depth == 0 and not in_string:
            break

        if in_string:
            current.append(ch)
            if escape:
                escape = False
            elif ch == "\\":
                escape = True
            elif ch == "'":
                in_string = False
            i += 1
            continue

        if ch == "'":
            current.append(ch)
            in_string = True
            escape = False
            i += 1
            continue

        if ch == "(":
            if depth > 0:
                current.append(ch)
            depth += 1
            i += 1
            continue

        if ch == ")":
            depth -= 1
            if depth > 0:
                current.append(ch)
            else:
                group = "".join(current).strip()
                if group:
                    yield group
                current = []
            i += 1
            continue

        if ch == "," and depth == 0:
            i += 1
            continue

        if depth > 0:
            current.append(ch)
        i += 1


def parse_insert(statement: str) -> Optional[Tuple[str, List[str], List[List]]]:
    prepared = statement.strip().rstrip(";")
    match = INSERT_HEADER_RE.match(prepared)
    if not match:
        return None

    table = match.group("table")
    columns_raw = match.group("columns")
    values_raw = match.group("values")

    columns = re.findall(r"`([^`]+)`", columns_raw)
    if not columns:
        return None

    rows: List[List] = []
    for group in iter_value_groups(values_raw):
        rows.append(parse_row(group))
    return table, columns, rows


def parse_create(statement: str) -> Optional[Tuple[str, List[str]]]:
    match = CREATE_TABLE_RE.search(statement)
    if not match:
        return None
    table = match.group("name")
    start = statement.find("(")
    end = statement.rfind(")")
    if start == -1 or end == -1 or end <= start:
        return table, []
    body = statement[start + 1 : end]
    columns: List[str] = []
    for line in body.splitlines():
        cleaned = line.strip().rstrip(",")
        if not cleaned or not cleaned.startswith("`"):
            continue
        upper = cleaned.upper()
        if upper.startswith(("PRIMARY KEY", "UNIQUE KEY", "KEY ", "CONSTRAINT", "FOREIGN KEY")):
            continue
        col_name = cleaned.split("`", 2)[1]
        columns.append(col_name)
    return table, columns


def iter_statements(sql_text: str) -> Iterator[Tuple[str, str]]:
    statement: List[str] = []
    in_string = False
    escape = False
    in_line_comment = False
    in_block_comment = False
    length = len(sql_text)
    i = 0

    while i < length:
        ch = sql_text[i]
        next_ch = sql_text[i + 1] if i + 1 < length else ""

        if in_line_comment:
            if ch == "\n":
                in_line_comment = False
            i += 1
            continue

        if in_block_comment:
            if ch == "*" and next_ch == "/":
                in_block_comment = False
                i += 2
            else:
                i += 1
            continue

        if not in_string:
            if ch == "-" and next_ch == "-":
                in_line_comment = True
                i += 2
                continue
            if ch == "/" and next_ch == "*":
                in_block_comment = True
                i += 2
                continue
            if ch == "/" and next_ch == "!":
                in_block_comment = True
                i += 2
                continue

        if not statement and ch.isspace():
            i += 1
            continue

        statement.append(ch)

        if in_string:
            if escape:
                escape = False
            elif ch == "\\":
                escape = True
            elif ch == "'":
                in_string = False
        else:
            if ch == "'":
                in_string = True
                escape = False
            elif ch == ";":
                stmt = "".join(statement).strip()
                statement.clear()
                if stmt:
                    upper = stmt.lstrip().upper()
                    if upper.startswith("INSERT INTO"):
                        yield "INSERT", stmt
                    elif upper.startswith("CREATE TABLE"):
                        yield "CREATE", stmt
                    else:
                        yield "OTHER", stmt
        i += 1

    remainder = "".join(statement).strip()
    if remainder:
        upper = remainder.lstrip().upper()
        if upper.startswith("INSERT INTO"):
            yield "INSERT", remainder
        elif upper.startswith("CREATE TABLE"):
            yield "CREATE", remainder
        else:
            yield "OTHER", remainder


def convert_dump_to_excel(input_path: pathlib.Path, output_path: pathlib.Path) -> None:
    sql_text = input_path.read_text(encoding="utf-8", errors="ignore")

    table_columns: Dict[str, List[str]] = {}
    table_rows: Dict[str, List[Dict[str, object]]] = defaultdict(list)

    for stmt_type, statement in iter_statements(sql_text):
        if stmt_type == "CREATE":
            result = parse_create(statement)
            if result:
                table, columns = result
                if table not in table_columns:
                    table_columns[table] = columns
            continue

        if stmt_type != "INSERT":
            continue

        parsed = parse_insert(statement)
        if not parsed:
            continue

        table, columns, rows = parsed
        clean_columns = [col.strip("`") for col in columns]

        existing = table_columns.get(table)
        if existing:
            for col in clean_columns:
                if col not in existing:
                    existing.append(col)
        else:
            table_columns[table] = list(clean_columns)
            existing = table_columns[table]

        for row in rows:
            if len(row) != len(clean_columns):
                raise ValueError(
                    f"Column/value count mismatch for table {table}: "
                    f"{len(clean_columns)} columns vs {len(row)} values"
                )
            table_rows[table].append(dict(zip(clean_columns, row)))

    if not table_rows:
        raise RuntimeError("No INSERT data found in the dump.")

    used_sheets: List[str] = []
    with pd.ExcelWriter(output_path, engine="openpyxl") as writer:
        for table, rows in table_rows.items():
            columns = table_columns.get(table) or []
            df = pd.DataFrame(rows)
            if columns:
                missing = [col for col in columns if col not in df.columns]
                for col in missing:
                    df[col] = None
                df = df[columns]
            sheet_name = sanitize_sheet_name(table, used_sheets)
            used_sheets.append(sheet_name)
            df.to_excel(writer, sheet_name=sheet_name, index=False)


def main() -> None:
    args = parse_arguments()
    input_path: pathlib.Path = args.input
    if not input_path.exists():
        raise FileNotFoundError(f"SQL dump not found: {input_path}")
    output_path: pathlib.Path = args.output or input_path.with_suffix(".xlsx")
    convert_dump_to_excel(input_path, output_path)
    print(f"Created {output_path}")


if __name__ == "__main__":
    main()
