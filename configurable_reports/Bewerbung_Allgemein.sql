SELECT
    MAX(CASE WHEN f.name = 'VORNAME' THEN c.content END) AS Vorname,
    MAX(CASE WHEN f.name = 'NACHNAME' THEN c.content END) AS Nachname,
    MAX(CASE WHEN f.name = 'ERSTWUNSCH' THEN uniw1.name END) AS Erstwunsch,
    MAX(CASE WHEN f.name = 'ZWEITWUNSCH' THEN uniw2.name END) AS Zweitwunsch,
    MAX(CASE WHEN f.name = 'DRITTWUNSCH' THEN uniw3.name END) AS Drittwunsch,
    FROM_UNIXTIME(e.timecreated, '%d.%m.%Y %H:%i') AS Einreichungsdatum,
    Case e.state
        WHEN 0 THEN 'EINGEGANGEN'
        WHEN 1 THEN 'ANGENOMMEN'
        WHEN 2 THEN 'NEUEINZUREICHEN'
        WHEN 3 THEN 'ABGELEHNT'
    END AS Status
FROM
     prefix_dataform_entries e
JOIN prefix_dataform_contents c ON c.entryid = e.id
JOIN prefix_dataform_fields f ON f.id = c.fieldid
JOIN prefix_dataform d ON d.id = e.dataid
LEFT JOIN prefix_dhbwio_universities uniw1
  ON uniw1.id = c.content AND f.name = 'ERSTWUNSCH'
LEFT JOIN prefix_dhbwio_universities uniw2
  ON uniw2.id = c.content AND f.name = 'ZWEITWUNSCH'
LEFT JOIN prefix_dhbwio_universities uniw3
  ON uniw3.id = c.content AND f.name = 'DRITTWUNSCH'
WHERE
    d.name = 'Anmeldung Auslandssemester'
%%FILTER_STARTTIME:e.timecreated:>%%
%%FILTER_ENDTIME:e.timecreated:<%%
GROUP BY
    e.id, e.timecreated
ORDER BY
    e.timecreated DESC