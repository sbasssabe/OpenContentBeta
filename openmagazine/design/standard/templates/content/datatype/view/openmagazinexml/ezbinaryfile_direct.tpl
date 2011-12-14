http:://{ezini('SiteSettings','SiteUrl')|washxml}{$attribute.content.filepath|ezroot(no)} {$attribute.content.original_filename|washxml} {$attribute.content.filesize|si( byte )}
