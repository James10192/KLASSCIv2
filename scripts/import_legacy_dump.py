#!/usr/bin/env python3
"""
Import data from an old MySQL/MariaDB dump into the current KLASSCI database.

The script reads INSERT statements from the legacy dump, keeps only the columns
that still exist in the current schema, and inserts the rows using parameterised
queries. Tables that no longer exist in the new schema are skipped.
"""

from __future__ import annotations

import argparse
import os
import re
from collections import defaultdict
from collections.abc import Iterator, Sequence
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, List, Tuple

import mysql.connector


INSERT_HEADER_RE = re.compile(
    r"INSERT\s+INTO\s+`(?P<table>[^`]+)`\s*\((?P<columns>.+?)\)\s*VALUES\s*(?P<values>.*)",
    re.IGNORECASE | re.DOTALL,
)


SKIP_TABLES = {
    "migrations",  # Laravel fills this during migrate:fresh
}


@dataclass
class ColumnInfo:
    nullable: bool
    default: str | None
    auto: bool


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Import a legacy SQL dump into the current KLASSCI database."
    )
    parser.add_argument("dump", type=Path, help="Path to the legacy SQL dump file")
    parser.add_argument(
        "--host", default=os.getenv("DB_HOST", "127.0.0.1"), help="Database host"
    )
    parser.add_argument(
        "--port",
        default=int(os.getenv("DB_PORT", "3306")),
        type=int,
        help="Database port",
    )
    parser.add_argument(
        "--user", default=os.getenv("DB_USERNAME", "root"), help="Database user"
    )
    parser.add_argument(
        "--password", default=os.getenv("DB_PASSWORD", ""), help="Database password"
    )
    parser.add_argument(
        "--database",
        default=os.getenv("DB_DATABASE"),
        required=os.getenv("DB_DATABASE") is None,
        help="Target database name",
    )
    parser.add_argument(
        "--batch-size",
        type=int,
        default=500,
        help="Number of rows per INSERT batch",
    )
    parser.add_argument(
        "--tables",
        type=str,
        help="Comma-separated list of tables to import (default: all from dump)",
    )
    return parser.parse_args()


def unescape_mysql(text: str) -> str:
    replacements = [
        (r"\0", "\0"),
        (r"\b", "\b"),
        (r"\n", "\n"),
        (r"\r", "\r"),
        (r"\t", "\t"),
        (r"\Z", "\x1A"),
        (r"\'", "'"),
        (r"\"", '"'),
        (r"\\", "\\"),
        ("''", "'"),
    ]
    for source, target in replacements:
        text = text.replace(source, target)
    return text


def convert_scalar(value: str):
    raw = value.strip()
    if not raw or raw.upper() == "NULL":
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

    for ch in content:
        if in_string:
            current.append(ch)
            if escape:
                escape = False
            elif ch == "\\":
                escape = True
            elif ch == "'":
                in_string = False
            continue

        if ch == "'":
            current.append(ch)
            in_string = True
            escape = False
            continue

        if ch == ",":
            values.append("".join(current).strip())
            current = []
            continue

        current.append(ch)

    if current:
        values.append("".join(current).strip())

    return [convert_scalar(item) for item in values]


def iter_value_groups(values_raw: str) -> Iterator[str]:
    depth = 0
    in_string = False
    escape = False
    current: List[str] = []

    for ch in values_raw:
        if in_string:
            current.append(ch)
            if escape:
                escape = False
            elif ch == "\\":
                escape = True
            elif ch == "'":
                in_string = False
            continue

        if ch == "'":
            current.append(ch)
            in_string = True
            escape = False
            continue

        if ch == "(":
            if depth > 0:
                current.append(ch)
            depth += 1
            continue

        if ch == ")":
            depth -= 1
            if depth > 0:
                current.append(ch)
            else:
                group = "".join(current).strip()
                current = []
                if group:
                    yield group
            continue

        if ch == "," and depth == 0:
            continue

        if depth > 0:
            current.append(ch)


def iter_statements(sql_text: str) -> Iterator[Tuple[str, str]]:
    statement: List[str] = []
    in_string = False
    escape = False
    in_line_comment = False
    in_block_comment = False

    i = 0
    length = len(sql_text)
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


def parse_insert(statement: str) -> Tuple[str, List[str], List[List]]:
    stmt = statement.strip().rstrip(";")
    match = INSERT_HEADER_RE.match(stmt)
    if not match:
        raise ValueError(f"Unsupported INSERT statement: {statement[:120]}...")

    table = match.group("table")
    columns_raw = match.group("columns")
    values_raw = match.group("values")

    columns = re.findall(r"`([^`]+)`", columns_raw)
    if not columns:
        raise ValueError(f"No columns detected for table {table}")

    rows = [parse_row(group) for group in iter_value_groups(values_raw)]
    return table, columns, rows


def load_schema(cursor, database: str) -> Dict[str, Dict[str, ColumnInfo]]:
    cursor.execute(
        """
        SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
        FROM information_schema.columns
        WHERE table_schema = %s
        ORDER BY ORDINAL_POSITION
        """,
        (database,),
    )
    schema: Dict[str, Dict[str, ColumnInfo]] = defaultdict(dict)
    for table, column, nullable, default, extra in cursor:
        schema[table][column] = ColumnInfo(
            nullable=nullable == "YES",
            default=default,
            auto="auto_increment" in (extra or ""),
        )
    return schema


def main() -> None:
    args = parse_arguments()
    if args.tables:
        target_tables = {name.strip() for name in args.tables.split(",") if name.strip()}
    else:
        target_tables = None
    dump_path: Path = args.dump
    if not dump_path.exists():
        raise FileNotFoundError(f"Dump not found: {dump_path}")

    connection = mysql.connector.connect(
        host=args.host,
        port=args.port,
        user=args.user,
        password=args.password,
        database=args.database,
        autocommit=False,
    )

    cursor = connection.cursor()
    schema = load_schema(cursor, args.database)

    sql_text = dump_path.read_text(encoding="utf-8", errors="ignore")

    cursor.execute("SET FOREIGN_KEY_CHECKS=0")

    table_batches: Dict[str, List[Tuple]] = defaultdict(list)
    batch_columns: Dict[str, Sequence[str]] = {}
    inserted_counts: Dict[str, int] = defaultdict(int)

    total_rows = 0
    skipped_tables = set()

    try:
        for stmt_type, statement in iter_statements(sql_text):
            if stmt_type != "INSERT":
                continue

            table, columns, rows = parse_insert(statement)
            if target_tables and table not in target_tables:
                skipped_tables.add(table)
                continue

            if table not in schema:
                skipped_tables.add(table)
                continue
            if table in SKIP_TABLES:
                skipped_tables.add(table)
                continue

            active_columns = [col for col in columns if col in schema[table]]
            if not active_columns:
                continue

            if table not in batch_columns:
                batch_columns[table] = active_columns
            else:
                if list(batch_columns[table]) != active_columns:
                    merged = [col for col in columns if col in schema[table]]
                    batch_columns[table] = merged

            col_to_index = {col: idx for idx, col in enumerate(columns)}
            target_indices = [col_to_index[col] for col in batch_columns[table]]

            for row in rows:
                extracted = tuple(
                    row[idx] if idx < len(row) else None for idx in target_indices
                )
                table_batches[table].append(extracted)
                total_rows += 1

                if len(table_batches[table]) >= args.batch_size:
                    placeholders = ",".join(["%s"] * len(batch_columns[table]))
                    insert_sql = (
                        f"INSERT INTO `{table}` ("
                        + ",".join(f"`{c}`" for c in batch_columns[table])
                        + f") VALUES ({placeholders})"
                    )
                    try:
                        cursor.executemany(insert_sql, table_batches[table])
                    except mysql.connector.Error as error:
                        raise RuntimeError(
                            f"Failed inserting batch into table '{table}'"
                        ) from error
                    inserted_counts[table] += len(table_batches[table])
                    table_batches[table].clear()

        for table, rows in table_batches.items():
            if not rows:
                continue
            columns = batch_columns[table]
            placeholders = ",".join(["%s"] * len(columns))
            insert_sql = (
                f"INSERT INTO `{table}` ("
                + ",".join(f"`{c}`" for c in columns)
                + f") VALUES ({placeholders})"
            )
            try:
                cursor.executemany(insert_sql, rows)
            except mysql.connector.Error as error:
                raise RuntimeError(f"Failed inserting batch into table '{table}'") from error
            inserted_counts[table] += len(rows)

        connection.commit()

    except Exception:
        connection.rollback()
        raise
    finally:
        cursor.execute("SET FOREIGN_KEY_CHECKS=1")
        cursor.close()
        connection.close()

    if skipped_tables:
        print(
            "Skipped tables:",
            ", ".join(sorted(skipped_tables)),
        )
    for table in sorted(inserted_counts):
        print(f"Inserted {inserted_counts[table]} rows into {table}.")
    print(f"Imported {total_rows} rows into {len(inserted_counts)} tables.")


if __name__ == "__main__":  # pragma: no cover - CLI entry point
    main()
