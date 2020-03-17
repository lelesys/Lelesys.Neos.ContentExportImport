# Lelesys.Neos.ContentExportImport
Export and Import part of content among Neos installations.

This is a helper package which provides commands to export a certain part of content and import to another Neos installation. This is useful when you have done something on a staging site and now you want to copy it to live site. E.g. If you built a Form on staging site with Node Based FormBuilder with quite number of fields and quite much settings with it. Now to get it onto live site you can export the Form node and import into live site page!
## Installation
`composer require lelesys/neos-contentexportimport`

## Help
./flow help content:export

./flow help content:import
