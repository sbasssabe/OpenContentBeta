{def $_classes = ezini( 'ContentTagMatch', 'Class', 'openmagazine.ini' )}
{if and( is_set( $node ), $_classes|contains( $node.class_identifier ) ) }
{include uri=concat('design:content/view/openmagazinexml.tpl') openmagazine_priority = $node.priority object = $node.object}
{else}
{foreach $nodes as $node}
{if $_classes|contains( $node.class_identifier ) }
{include uri=concat('design:content/view/openmagazinexml.tpl') openmagazine_priority = $openmagazine_priority[$node.node_id] object = $node.object}
{/if}
{/foreach}
{/if}
