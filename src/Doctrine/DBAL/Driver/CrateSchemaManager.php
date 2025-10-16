<?php

namespace SkyDiablo\ReactCrate\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;

/**
 * Schema manager for CrateDB.
 */
class CrateSchemaManager extends PostgreSQLSchemaManager
{
    /**
     * {@inheritDoc}
     */
    protected function determineCurrentSchemaName(): ?string
    {
        // CrateDB uses 'doc' as the default schema
        $currentSchema = parent::determineCurrentSchemaName();
        
        // If current_schema() returns null or empty, use 'doc' as default
        if (empty($currentSchema)) {
            return 'doc';
        }
        
        return $currentSchema;
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        $params = [];
        $conditions = [];
        if ($tableName !== null) {
            if (str_contains($tableName, '.')) {
                [$schemaName, $tableName] = explode('.', $tableName);
                $conditions[] = 'n.nspname = ?';
                $params[] = $schemaName;
            } else {
                $conditions[] = 'n.nspname = ANY(current_schemas(false))';
            }
            $conditions[] = 'c.relname = ?';
            $params[] = $tableName;
        }
        $conditions[] = "n.nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')";

        $sql = sprintf(
            <<<'SQL'
        SELECT quote_ident(n.nspname)               AS schema_name,
               quote_ident(c.relname)               AS table_name,
               quote_ident(a.attname)               AS field,
               CASE 
                   WHEN t.typname LIKE '\_%%' THEN trim(leading '_' from t.typname)
                   ELSE t.typname
               END                                  AS type,
               format_type(a.atttypid, a.atttypmod) AS complete_type,
               CASE 
                   WHEN bt.typname LIKE '\_%%' THEN trim(leading '_' from bt.typname)
                   ELSE bt.typname
               END                                  AS domain_type,
               format_type(bt.oid, t.typtypmod)     AS domain_complete_type,
               a.attnotnull                         AS isnotnull,
               a.attidentity,
               (%s)                                 AS "default",
               dsc.description                      AS comment,
               NULL                                 AS collation  -- Set to NULL since pg_collation is not supported
        FROM pg_attribute a
                 JOIN pg_class c
                      ON c.oid = a.attrelid
                 JOIN pg_namespace n
                      ON n.oid = c.relnamespace
                 JOIN pg_type t
                      ON t.oid = a.atttypid
                 LEFT JOIN pg_type bt
                           ON t.typtype = 'd'
                               AND bt.oid = t.typbasetype
                 LEFT JOIN pg_description dsc
                           ON dsc.objoid = c.oid AND dsc.objsubid = a.attnum
                 LEFT JOIN pg_depend dep
                           ON dep.objid = c.oid
                               AND dep.deptype = 'e'
                               AND dep.classid = (SELECT oid FROM pg_class WHERE relname = 'pg_class')
        WHERE %s
          -- 'r' for regular tables - 'p' for partitioned tables
          AND c.relkind IN ('r', 'p')
          AND a.attnum > 0
          AND dep.refobjid IS NULL
        ORDER BY n.nspname,
            c.relname,
            a.attnum
        SQL,
            $this->platform->getDefaultColumnValueSQLSnippet(),
            implode(' AND ', $conditions),
        );

        return $this->connection->executeQuery($sql, $params);
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        $params = [];
        $conditions = [];
        if ($tableName !== null) {
            if (str_contains($tableName, '.')) {
                [$schemaName, $tableName] = explode('.', $tableName);
                $conditions[] = 'n.nspname = ?';
                $params[] = $schemaName;
            } else {
                $conditions[] = 'n.nspname = ANY(current_schemas(false))';
            }
            $conditions[] = 'c.relname = ?';
            $params[] = $tableName;
        }
        $conditions[] = "n.nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')";

        $sql = sprintf(
            <<<'SQL'
        SELECT quote_ident(n.nspname) AS schema_name,
               quote_ident(c.relname) AS table_name,
               quote_ident(ic.relname) AS relname,
               i.indisunique,
               i.indisprimary,
               pg_get_expr(i.indpred, i.indrelid) AS "where",
               quote_ident(a.attname) AS attname,
               s.ord
        FROM pg_index i
             JOIN pg_class c ON c.oid = i.indrelid
             JOIN pg_namespace n ON n.oid = c.relnamespace
             JOIN pg_class ic ON ic.oid = i.indexrelid
             CROSS JOIN generate_series(1, 32) s(ord)
             JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum = i.indkey[s.ord]
        WHERE %s
          AND i.indkey[s.ord] <> 0
        ORDER BY schema_name, table_name, relname, s.ord
        SQL,
            implode(' AND ', $conditions)
        );

        return $this->connection->executeQuery($sql, $params);
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        $params = [];
        $conditions = [];
        if ($tableName !== null) {
            if (str_contains($tableName, '.')) {
                [$schemaName, $tableName] = explode('.', $tableName);
                $conditions[] = 'n.nspname = ?';
                $params[] = $schemaName;
            } else {
                $conditions[] = 'n.nspname = ANY(current_schemas(false))';
            }
            $conditions[] = 'c.relname = ?';
            $params[] = $tableName;
        }
        $conditions[] = "n.nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')";

        $sql = sprintf(
            <<<'SQL'
        SELECT 
            quote_ident(schema_name) AS schema_name,
            quote_ident(table_name) AS table_name,
            quote_ident(conname) AS conname,
            'FOREIGN KEY (' || string_agg(quote_ident(local_att), ', ') || ') REFERENCES ' ||
            quote_ident(f_nsp) || '.' || quote_ident(f_rel) || ' (' || string_agg(quote_ident(foreign_att), ', ') || ')' ||
            CASE WHEN confupdtype <> 'a' THEN ' ON UPDATE ' || CASE confupdtype
                WHEN 'c' THEN 'CASCADE'
                WHEN 'r' THEN 'RESTRICT'
                WHEN 'n' THEN 'SET NULL'
                WHEN 'd' THEN 'SET DEFAULT'
                WHEN 'a' THEN 'NO ACTION'
                END ELSE '' END ||
            CASE WHEN confdeltype <> 'a' THEN ' ON DELETE ' || CASE confdeltype
                WHEN 'c' THEN 'CASCADE'
                WHEN 'r' THEN 'RESTRICT'
                WHEN 'n' THEN 'SET NULL'
                WHEN 'd' THEN 'SET DEFAULT'
                WHEN 'a' THEN 'NO ACTION'
                END ELSE '' END AS condef,
            condeferrable,
            condeferred
        FROM (
            SELECT 
                tn.nspname AS schema_name,
                tc.relname AS table_name,
                r.conname,
                r.condeferrable,
                r.condeferred,
                r.confupdtype,
                r.confdeltype,
                fn.nspname AS f_nsp,
                fc.relname AS f_rel,
                s.ord,
                la.attname AS local_att,
                fa.attname AS foreign_att
            FROM pg_constraint r
                JOIN pg_class tc ON tc.oid = r.conrelid
                JOIN pg_namespace tn ON tn.oid = tc.relnamespace
                JOIN pg_class fc ON fc.oid = r.confrelid
                JOIN pg_namespace fn ON fn.oid = fc.relnamespace
                CROSS JOIN generate_series(1, 32) s(ord)
                LEFT JOIN pg_attribute la ON la.attrelid = r.conrelid 
                    AND la.attnum = r.conkey[s.ord] 
                    AND r.conkey[s.ord] <> 0
                LEFT JOIN pg_attribute fa ON fa.attrelid = r.confrelid 
                    AND fa.attnum = r.confkey[s.ord] 
                    AND r.confkey[s.ord] <> 0
            WHERE r.conrelid IN (
                SELECT c.oid
                FROM pg_class c
                JOIN pg_namespace n ON n.oid = c.relnamespace
                WHERE %s
            )
            AND r.contype = 'f'
        ) sub
        WHERE local_att IS NOT NULL OR foreign_att IS NOT NULL
        GROUP BY schema_name, table_name, conname, condeferrable, condeferred, confupdtype, confdeltype, f_nsp, f_rel
        ORDER BY schema_name, table_name
        SQL,
        implode(' AND ', $conditions)
    );

    return $this->connection->executeQuery($sql, $params);
}
}
