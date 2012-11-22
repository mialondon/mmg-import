The WordPress plugin 'mmg-import' allows users to search for terms in museum APIs and import the resulting object listings into database tables for use in museum metadata games (with the mmg plugin).

Notes on import adaptations:

Powerhouse data doesn't have an 'interpretative' date, but instead uses date_earliest and date_latest, current Science Museum API has a single 'interpretative' date - a summary date, in a sense.  Various Culture Grid providers use date ranges or single dates, with many variations in how they're listed - would need lots of data cleaning to actually use in searches rather than just displaying.

'interpretative x' is a method we use at the Science Museum to present a public-facing 'summarised' data and/or place while keeping the more complicated (e.g. 'this is a replica made in 1932 but we're using it to tell a story about an object made in 1654'), qualified (i.e. relating to specific relationships with e.g. types of people, places, etc) data intact for specialist use, searching etc.

Changelog:
December 22, 2010: created the plugin.  Uses the shortcode [mmg-import] - add it to a page in WordPress, displays a search box which runs a query against an API and imports the results into the database.  Lots to do still, e.g. it doesn't add the tables if they're not already there.

December 26, 2010: scaphoid fracture, doh!  Work willl have to wait.

January 15, 2011: added CultureGrid json access with Ian @ianibbo. Lots of tweaks needed eg ability to store URL and optional API key for more than one API, and select API for use.

January 28, 2011: added config option for how many records to import.

November 2012: updates to do: investigate Cooper-Hewitt and Europeana (updated) APIs