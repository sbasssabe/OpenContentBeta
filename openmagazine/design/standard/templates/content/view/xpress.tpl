{def $attributes = ezini( 'ContentTagMatch', 'Attribute', 'openmagazine.ini' )}
{foreach $attributes as $item}
{if is_set($object.data_map[$item])}
{*attribute_view_gui view = 'xpress' attribute = $object.data_map[$item]*}
{include uri=concat( 'design:content/datatype/view/xpress/', $object.data_map[$item].data_type_string,'.tpl' ) attribute=$object.data_map[$item]}
{/if}
{/foreach}