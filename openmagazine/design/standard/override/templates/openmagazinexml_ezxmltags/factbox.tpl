{if ezini( 'ContentTagMatch', 'XMLTag', 'openmagazine.ini' )|contains( 'factbox' ) }
    {foreach ezini( 'ContentTagMatch', 'XMLTag', 'openmagazine.ini' ) as $k => $t}
        {if eq( $t, 'factbox' )}
        {*@TODO*}
        <{concat($k,1)}>{$content|strip_tags|washxml}</{concat($k,1)}>
        {break}
        {/if}
    {/foreach}
{/if}