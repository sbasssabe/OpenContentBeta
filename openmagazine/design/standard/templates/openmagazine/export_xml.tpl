<form enctype="multipart/form-data" method="post" action={"/openmagazine/export_xml"|ezurl}>

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="content-view-openmagazine">
    <div class="class-frontpage">
    
    <div class="attribute-header">
        <h1>{"Export your content in OpenMagazine XML format"|i18n("extension/openmagazine")}</h1>
    </div>
    <div class="attribute-description">
        
        {if $selectedNodes}
        <p>{"You can click on Remove to delete items from the export queue."|i18n('extension/openmagazine')}</p>
        <p>{"You can click on Select to add other items."|i18n('extension/openmagazine')}</p>
        <table cellspacing="0" class="list">
        <tbody>
            <tr>
                <th class="tight"></th>
                <th>{"Name"|i18n("design/standard/content/browse")}</th>
                <th>{"Class"|i18n("design/standard/content/browse")}</th>
                <th class="tight">{"Priority"|i18n("extension/openmagazine")}</th>
            </tr>
        {def $node = false()}
        {foreach $selectedNodes as $index => $nodeID sequence array('bglight','bgdark') as $style}
        {set $node = fetch( 'content', 'node', hash( 'node_id', $nodeID )) }
            <tr class="{$style}">
                <td>
                    <input type="checkbox" value="{$node.node_id}" name="RemoveNodeIDArray[]">
                </td>
                <td>{$node.name|wash()}</td>
                <td>{$node.class_name|wash()}</td>
                <td>
                    <input type="text" title="{"Use this field to set the priority of the xml tags."|i18n('extension/openmagazine')}" value="{$index}" size="30" name="OpenMagazinePriority[{$node.node_id}]" class="halfbox">
                </td>
            </tr>
        {/foreach}
        {undef $node}
        </tbody>
        </table>
        {else}
            <p>{"Click on select to navigate through the content and choose which objects to export."|i18n('extension/openmagazine')}</p>
        {/if}
        
        <div class="block">
            {if $selectedNodes}   
                <input class="button" type="submit" name="RemoveButton" value="{'Remove selected'|i18n( 'design/admin/shop/basket' )}" />
            {/if}
                <input class="{if $selectedNodes|not()}default{/if}button" type="submit" name="BrowseButton" value="{'Select'|i18n('design/standard/content/browse')}" />
            {if $selectedNodes}   
                <input type="hidden" name="selectedNodeIDString" value="{$selectedNodes|implode(-)}" />
                <input class="defaultbutton" type="submit" name="ExportButton" value="{'Export to file'|i18n( 'design/standard/package')}" />
            {/if}
        </div> 
    
    </div>

</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>

</form>
