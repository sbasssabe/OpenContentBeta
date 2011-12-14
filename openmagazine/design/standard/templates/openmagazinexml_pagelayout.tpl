{'Content-Type: text/xml'|httpheader()}<?xml version="1.0" encoding="{httpcharset()|upcase()}"?>
<root>
{$module_result.content}
</root>
{* try to make sure the eventual debug report does not break xml by encapsulating it in a comment *}
<!-- {"Powered by eZ Publish open source content management system and development framework. http://ez.no"|i18n("design/base",)} -->
<!--DEBUG_REPORT-->
