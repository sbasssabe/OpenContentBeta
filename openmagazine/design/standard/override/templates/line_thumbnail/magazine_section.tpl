{ezscript_require( array( 'ezjsc::jquery', 'jquery.svg.js', 'jquery.svgdom.min.js' ) )}
{literal}
<script type="text/javascript">
var loadDone = function(svg, error) { 
    if (error) svg.text(10, 20, error); 
};
$(function() {
    $('.repository-svg').each( function(){
        var id = $(this).attr('id');
        var params = id.split('-');
        $('#'+id).svg();
        var svgWidth = 70;
        var svgHeight = 50;
        var svg = $('#'+id).svg('get')
        .configure({
            width: svgWidth,
            height: svgHeight
        })
        .load( '/openmagazine/svg/' + params[1] + '/'+ params[2] + '/0.05', {addTo: false, changeSize: false, onLoad: loadDone}); 
    });
});
</script>
{/literal}

{def $idml_attribute_name = ezini( 'FileImport', 'DefaultImportIdmlAttribute', 'idml.ini' )}
<div class="content-view-line-thumbnail">
    <div class="class-{$node.object.class_identifier}">
        {if is_set( $node.url_alias )}
        <h2><a href="{$node.url_alias|ezurl('no')}" title="{$node.name|wash}">{$node.name|wash|shorten(17)}</a></h2>
        {else}
        <h2>{$node.name|wash|shorten(17)}</h2>
        {/if}
        <div class="content-file">
            {def $idml_attribute = $node.data_map.$idml_attribute_name.content}
            {def $count = 1}
            {foreach $idml_attribute.spreads as $id => $spread}
                <div id="svgload-{$node.node_id}-{$id}" class="repository-svg"></div>
                {set $count = $count|inc()}
                {if $count|eq(3)}
                {break}
                {/if}
            {/foreach}
        </div>
        <div class="thumbnail-class-name"><p>{$node.class_name|wash}</p></div>
    </div>
</div>