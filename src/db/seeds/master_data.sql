--name: check-table-existence
SELECT count(*) as exist 
FROM information_schema.tables 
WHERE table_name = ? 
LIMIT 1;