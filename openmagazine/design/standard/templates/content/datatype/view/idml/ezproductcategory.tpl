{if $attribute.has_content}
{$attribute.content.name|washxml}
{else}
{'None'|i18n( 'design/standard/content/datatype' )}
{/if}