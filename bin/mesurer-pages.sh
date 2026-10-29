#!/bin/sh
# Temps de réponse de pages KLASSCI, médiane et p95 sur N appels.
#
#   sh bin/mesurer-pages.sh https://presentation.klassci.com 40 /login /esbtp/inscriptions
#
# Une page protégée demande une session : passer le cookie par la variable
# d'environnement KLASSCI_COOKIE (jamais en argument, jamais dans un fichier).
# Mesure ce que voit le navigateur (temps total curl), réseau compris : pour
# comparer avant/après, mesurer au même moment de la journée, en alternant.
base="$1"; n="${2:-30}"; shift 2
for chemin in "$@"; do
  i=0; : > /tmp/mesure.$$
  while [ $i -lt "$n" ]; do
    curl -s -o /dev/null -w '%{http_code} %{time_total}\n' ${KLASSCI_COOKIE:+-H "Cookie: $KLASSCI_COOKIE"} "$base$chemin" >> /tmp/mesure.$$
    i=$((i+1))
  done
  sort -k2 -n /tmp/mesure.$$ | awk -v c="$chemin" '{t[NR]=$2*1000; s[$1]++} END {m=t[int((NR+1)/2)]; p=t[int(NR*0.95+0.999)]; printf "%-40s n=%d  médiane %.0f ms  p95 %.0f ms  statuts", c, NR, m, p; for (k in s) printf " %s×%d", k, s[k]; print ""}'
done
rm -f /tmp/mesure.$$
