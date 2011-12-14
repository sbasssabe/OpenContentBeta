{section show=$attribute.content}
http:://{ezini('SiteSettings','SiteUrl')|washxml}{concat( 'content/download/', $attribute.contentobject_id, '/', $attribute.id,'/version/', $attribute.version , '/file/', $attribute.content.original_filename|urlencode )|ezurl(no)} - {$attribute.content.original_filename|washxml} - {$attribute.content.filesize|si( byte )}
{/section}
