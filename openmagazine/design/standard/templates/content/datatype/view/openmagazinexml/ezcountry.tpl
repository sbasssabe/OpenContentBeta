{if $attribute.has_content}
   {if is_array( $attribute.content.value )}
       {foreach $attribute.content.value as $country}
           {$country.Name|washxml}
       {/foreach}
   {else}
       {$attribute.content.value|washxml}
   {/if}
{/if}