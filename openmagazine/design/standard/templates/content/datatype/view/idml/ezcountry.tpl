{if $attribute.has_content}
   {if is_array( $attribute.content.value )}
       {foreach $attribute.content.value as $country}
           {$country.Name|washxml}
           {delimiter}, {/delimiter}
       {/foreach}
   {else}
       {$attribute.content.value|washxml}
   {/if}
{else}
   {'Not specified'|i18n( 'design/standard/content/datatype' )}
{/if}