import sys
from pathlib import Path
from dotenv import load_dotenv
import os
import psycopg

def is_meaningful(chunk: str) -> bool:
    # Considera "vazio" se só tiver comentários/linhas em branco
    lines = []
    for ln in chunk.splitlines():
        s = ln.strip()
        if not s or s.startswith("--"):
            continue
        lines.append(s)
    return len(lines) > 0

def main():
    if len(sys.argv) != 2:
        print("Uso: python scripts/db_apply_sql.py <arquivo.sql>")
        sys.exit(2)

    load_dotenv()

    sql_path = Path(sys.argv[1])

    # utf-8-sig remove BOM (se existir) automaticamente
    sql = sql_path.read_text(encoding="utf-8-sig")

    conninfo = (
        f"host={os.getenv('DB_HOST')} "
        f"port={os.getenv('DB_PORT')} "
        f"dbname={os.getenv('DB_NAME')} "
        f"user={os.getenv('DB_USER')} "
        f"password={os.getenv('DB_PASS')} "
        f"sslmode=require"
    )

    statements = [s.strip() for s in sql.split(";")]

    with psycopg.connect(conninfo) as conn:
        with conn.cursor() as cur:
            for stmt in statements:
                if not stmt:
                    continue
                if not is_meaningful(stmt):
                    continue
                cur.execute(stmt)
        conn.commit()

    print(f"OK: migration aplicada -> {sql_path}")

if __name__ == "__main__":
    main()
