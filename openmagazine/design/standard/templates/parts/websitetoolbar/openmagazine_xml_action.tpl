{if and( is_set( $current_node ), ezini( 'ContentTagMatch', 'Class', 'openmagazine.ini' )|contains( $current_node.class_identifier ) ) }
    <a href={concat( "openmagazine/do_export_xml/", $current_node.node_id )|ezurl()} title="{'Export in OpenMagazine XML'|i18n( 'extension/openmagazine' )}">
        <img class="ezwt-input-image" src={'openmagazine/ezwt-om-xml.png'|ezimage()} alt="{'Export in OpenMagazine XML'|i18n( 'extension/openmagazine' )}"/>
    </a>
{/if}