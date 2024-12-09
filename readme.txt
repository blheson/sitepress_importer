# Sitepress Importer

A plugin for importing Sitepress to Wordpress
## Installation

Download [zip file](https://github.com/blheson/sitepress_importer/archive/refs/heads/v1.0.1.zip) from github and upload on wordpress

## Usage
1. Navigate to /wp-admin/tools.php?page=sitepress-importer
2. Upload Template and Page csv

### CSV Format Requirements
Ensure Template CSV has these headers
`array( 'ID', 'SiteID', 'Title', 'Content', 'DateCreated', 'CreatedBy', 'DateModified', 'ModifiedBy', 'EditableAreaCount', 'isDefault' )`;

Ensure Page CSV has these headers
`array( 'ID', 'SiteID', 'ParentPageID', 'FolderID', 'PositionInGroup', 'TemplateID', 'PageName', 'PageURL', 'PageType', 'PageTitle', 'PageWindowTitle', 'PageDescription', 'PageKeywords', 'PageContent', 'PageASPXIncludeFile', 'Version', 'IsPublished', 'IsSearchable', 'IsInNavigation', 'IsIndexedInternally', 'RequireAuthentication', 'RequireSSL', 'CreatedBy', 'DateCreated', 'LastModifiedBy', 'LastModified', 'IsEndPoint', 'HasComments', 'StaticID', 'ExplicitSecurity', 'CanonicalLinkOverride' )`

## Known Limitation
Page parsing is in beta hence, there might be unexpected page formating

## Requirements
Requires PHP 7.4.0 or higher

## License

[GPL-3.0](https://choosealicense.com/licenses/agpl-3.0)