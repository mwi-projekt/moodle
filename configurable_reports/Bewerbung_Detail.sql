SELECT
    MAX(CASE WHEN f.name = 'VORNAME' THEN c.content END) AS Vorname,
    MAX(CASE WHEN f.name = 'NACHNAME' THEN c.content END) AS Nachname,
	Case e.state
        WHEN 0 THEN 'EINGEGANGEN'
        WHEN 1 THEN 'ANGENOMMEN'
        WHEN 2 THEN 'NEUEINZUREICHEN'
        WHEN 3 THEN 'ABGELEHNT'
    END AS Aktueller_Stand,
    FROM_UNIXTIME(MAX(CASE WHEN f.name = 'GEBURTSDATUM' THEN c.content END), '%d.%m.%Y') AS Geburtsdatum,
    MAX(CASE WHEN f.name = 'EMAIL' THEN c.content END) AS EMail,
	MAX(CASE WHEN f.name = 'STUDIENGANG' THEN studiengang.studiengang END) AS Studiengang,
    MAX(CASE WHEN f.name = 'STUDIENRICHTUNG' THEN c.content END) AS Studienrichtung,
    MAX(CASE WHEN f.name = 'STUDIENGANGSLEITUNG' THEN c.content END) AS Studiengangsleitung,
    MAX(CASE WHEN f.name = 'AKTUELLES_SEMESTER' THEN c.content END) AS Semester_zum_Zeitpunkt_der_Anmeldung,
	MAX(CASE WHEN f.name = 'ERSTWUNSCH' THEN uniw1.name END) AS Erstwunsch,
    MAX(CASE WHEN f.name = 'ZWEITWUNSCH' THEN uniw2.name END) AS Zweitwunsch,
    MAX(CASE WHEN f.name = 'DRITTWUNSCH' THEN uniw3.name END) AS Drittwunsch,
	MAX(CASE WHEN f.name = 'ABSPRACHE_MIT_UNTERNEHMEN' THEN absprache.absprache END) AS Absprache_Unternehmen,
    MAX(CASE WHEN f.name = 'ABSPRACHE_MIT_STUDIENGANGSLEITUNG' THEN absprache.absprache END) AS Absprache_Studiengangsleitung,
	MAX(CASE WHEN f.name = 'BENACHTEILIGUNG_BILDUNGSCHANCEN' THEN c.content END) AS Benachteiligung,
    MAX(CASE WHEN f.name = 'VEROEFFENTLICHUNG_MAILADRESSE_UND_BERICHT' THEN absprache.absprache END) AS Einverständniserklärung_Bericht,
    MAX(CASE WHEN f.name = 'NACHRICHT' THEN c.content END) AS Nachricht,
	MAX(CASE WHEN f.name = 'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ' THEN absprache.absprache END) AS Einverständniserklärung_Datenschutz 
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
LEFT JOIN (
    SELECT '1' AS id, 'Ja' AS absprache
    UNION SELECT '2', 'Nein'
) absprache ON absprache.id = c.content AND (f.name = 'ABSPRACHE_MIT_UNTERNEHMEN' OR f.name = 'ABSPRACHE_MIT_STUDIENGANGSLEITUNG' OR f.name = 'VEROEFFENTLICHUNG_MAILADRESSE_UND_BERICHT' OR f.name = 'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ')
LEFT JOIN (
    SELECT '1' AS id, 'Angewandte Gesundheits- und Pflegewissenschaften' AS studiengang
    UNION SELECT '2', 'Angewandte Hebammenwissenschaft'
  UNION SELECT '3', 'Physician Assistant / Arztassistent'
  UNION SELECT '4', 'Elektro- und Informationstechnik'
  UNION SELECT '5', 'Informatik'
  UNION SELECT '6', 'Maschinenbau'
  UNION SELECT '7', 'Mechatronik'
  UNION SELECT '8', 'Papiertechnik'
  UNION SELECT '9', 'Sicherheitswesen'
  UNION SELECT '10', 'Sustainable Science and Technology'
  UNION SELECT '11', 'Wirtschaftsingenieurwesen'
  UNION SELECT '12', 'BWL - Bank'
  UNION SELECT '13', 'BWL - Deutsch-Franz. Management'
  UNION SELECT '14', 'BWL - Digital Business Management'
  UNION SELECT '15', 'BWL - Digital Commerce Management'
  UNION SELECT '16', 'BWL - Handel'
  UNION SELECT '17', 'BWL - Industrie'
  UNION SELECT '18', 'BWL - Versicherung'
  UNION SELECT '19', 'Data Science und Künstliche Intelligenz'
  UNION SELECT '20', 'RSW - Steuern und Prüfungswesen'
  UNION SELECT '21', 'Unternehmertum'
  UNION SELECT '22', 'Wirtschaftsinformatik'
) studiengang ON studiengang.id = c.content AND f.name = 'STUDIENGANG'
WHERE
    d.name = 'Anmeldung Auslandssemester'
%%FILTER_STARTTIME:e.timecreated:>%%
%%FILTER_ENDTIME:e.timecreated:<%%
GROUP BY
    e.id, e.timecreated
ORDER BY
    e.timecreated DESC