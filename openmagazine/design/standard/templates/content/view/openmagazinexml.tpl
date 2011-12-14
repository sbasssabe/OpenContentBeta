{def $classes = ezini( 'ContentTagMatch', 'Class', 'openmagazine.ini' )
     $attributes = ezini( 'ContentTagMatch', 'Attribute', 'openmagazine.ini' )
     $current_class = false()
     $key_class = false()
     $key_attribute = false()
     $xml_attributes = hash()}
     
{foreach $object.data_map as $attribute}
{if and( $attribute.has_content, $attributes|contains( $attribute.contentclass_attribute_identifier ) )}
{set $current_class = $object.class_identifier}
    {foreach $classes as $kc => $ic}
    {if eq($ic, $current_class)}
    {set $key_class = $kc}
    {break}
    {/if}
    {/foreach}
    {foreach $attributes as $ka => $ia}
    {if eq($ia, $attribute.contentclass_attribute_identifier)}
    {set $key_attribute = $ka}
    {break}
    {/if}
    {/foreach}

{*set $xml_attributes = hash( 'data_type_string', $attribute.data_type_string )*}

{switch match=$attribute.data_type_string}
    {case match='ezimage'}
    {set $xml_attributes = $xml_attributes|merge( hash( 'href', concat( 'file:/', ezini('ExportImagesSettings','LocalImagePath','openmagazine.ini')|washxml , $attribute.content.original.filename ) ) )}
    {/case}
    {case}{/case}
{/switch}
    
<{concat( $key_class, $key_attribute, $openmagazine_priority )|washxml()}{if $xml_attributes|count()|gt(0)}{foreach $xml_attributes as $key => $value} {$key||washxml()}="{$value||washxml()}"{/foreach}{/if}>{include uri=concat('design:content/datatype/view/openmagazinexml/', $attribute.data_type_string, '.tpl')}</{concat( $key_class, $key_attribute, $openmagazine_priority )|washxml()}>
{/if}
{/foreach}
