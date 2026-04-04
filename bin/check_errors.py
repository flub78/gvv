#!/usr/bin/env python3
"""
Run a command and report any new ERROR entries in CodeIgniter logs.

Usage:
    python3 bin/check_errors.py <command> [args...]
    python3 bin/check_errors.py ./run-all-tests.sh
    python3 bin/check_errors.py php -l application/controllers/welcome.php

    # Lancer la suite de tests et vérifier les erreurs CI                         
    python3 bin/check_errors.py ./run-all-tests.sh                                
                                                                                
    # Valider un contrôleur PHP                                                   
    python3 bin/check_errors.py php -l application/controllers/welcome.php       
                                                                                
    # Avec les tests Playwright                                                
    python3 ../bin/check_errors.py npx playwright test --reporter=line              
"""

import sys
import os
import subprocess
import glob
from datetime import date

LOG_DIR = os.path.join(os.path.dirname(__file__), '..', 'application', 'logs')


def get_log_file(day: date) -> str:
    return os.path.join(LOG_DIR, f"log-{day.isoformat()}.php")


def count_lines(path: str) -> int:
    if not os.path.exists(path):
        return 0
    with open(path, 'r', errors='replace') as f:
        return sum(1 for _ in f)


def read_lines_from(path: str, offset: int) -> list[str]:
    if not os.path.exists(path):
        return []
    with open(path, 'r', errors='replace') as f:
        lines = f.readlines()
    return lines[offset:]


def extract_errors(lines: list[str]) -> list[str]:
    return [l.rstrip() for l in lines if l.startswith('ERROR')]


def main():
    if len(sys.argv) < 2:
        print(f"Usage: {sys.argv[0]} <command> [args...]", file=sys.stderr)
        sys.exit(1)

    command = sys.argv[1:]

    # Snapshot current log state (today's file may not exist yet)
    today = date.today()
    log_path = get_log_file(today)
    snapshot_offset = count_lines(log_path)

    print(f"[check_errors] Log: {log_path} ({snapshot_offset} lines before run)")

    # Run the command in the caller's working directory
    result = subprocess.run(command)
    exit_code = result.returncode

    # Read new log lines (the date might have changed — handle both days)
    new_errors = extract_errors(read_lines_from(log_path, snapshot_offset))

    new_day = date.today()
    if new_day != today:
        new_log_path = get_log_file(new_day)
        new_errors += extract_errors(read_lines_from(new_log_path, 0))

    # Report
    print()
    if new_errors:
        print(f"[check_errors] {len(new_errors)} new ERROR(s) in logs:")
        for err in new_errors:
            print(f"  {err}")
    else:
        print("[check_errors] No new errors in logs.")

    sys.exit(exit_code)


if __name__ == '__main__':
    main()
