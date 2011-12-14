{set scope=global persistent_variable=hash('left_menu', false(),
                                           'extra_menu', false(),
                                           'show_path', true())}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<form id="ezwt-sort-form" method="post" action={'content/action'|ezurl}>
<input type="hidden" name="ContentNodeID" value="{$node.node_id}" />
<input type="hidden" name="ContentObjectID" value='{$node.object.id}' />

{def $item_type = ezpreference( 'user_list_limit' )
     $priority_sorting = true()
     $node_can_edit = $node.can_edit
     $node_name     = $node.name
     $can_remove    = false()
     $number_of_items = min( $item_type, 3)|choose( 10, 10, 25, 50 )
     $children_count = fetch( content, list_count, hash( parent_node_id, $node.node_id,
                                                      objectname_filter, $view_parameters.namefilter ) )
     $children = fetch( content, list, hash( parent_node_id, $node.node_id,
                                          sort_by, array( 'priority', false() ),
                                          limit, $number_of_items,
                                          offset, $view_parameters.offset,
                                          objectname_filter, $view_parameters.namefilter ) ) }

<div class="attribute-header">
    <h1 class="long"><a href={$node.url_alias|ezurl}>{$node_name|wash}</a>&nbsp;-&nbsp;{'OpenMagazine sort sub items [%children_count]'|i18n( 'extension/openmagazine',, hash( '%children_count', $children_count ) )}</h1>
</div>

{if $children}
<div class="block">

    {switch match=$number_of_items}
        {case match=25}
        <a href={'/user/preferences/set/user_list_limit/1'|ezurl} title="{'Show 10 items per page.'|i18n( 'design/standard/websitetoolbar/sort' )}">10</a>
        <span class="current">25</span>
        <a href={'/user/preferences/set/user_list_limit/3'|ezurl} title="{'Show 50 items per page.'|i18n( 'design/standard/websitetoolbar/sort' )}">50</a>
        {/case}

        {case match=50}
        <a href={'/user/preferences/set/user_list_limit/1'|ezurl} title="{'Show 10 items per page.'|i18n( 'design/standard/websitetoolbar/sort' )}">10</a>
        <a href={'/user/preferences/set/user_list_limit/2'|ezurl} title="{'Show 25 items per page.'|i18n( 'design/standard/websitetoolbar/sort' )}">25</a>
        <span class="current">50</span>
        {/case}

        {case}
        <span class="current">10</span>
        <a href={'/user/preferences/set/user_list_limit/2'|ezurl} title="{'Show 25 items per page.'|i18n( 'design/standard/websitetoolbar/sort' )}">25</a>
        <a href={'/user/preferences/set/user_list_limit/3'|ezurl} title="{'Show 50 items per page.'|i18n( 'design/standard/websitetoolbar/sort' )}">50</a>
        {/case}
    {/switch}

</div>

<table id="ezwt-sort-list" class="list" cellspacing="0">
    <tr>
        <th class="name">{'Name'|i18n( 'design/standard/websitetoolbar/sort' )}</th>
        <th class="class">{'Type'|i18n( 'design/standard/websitetoolbar/sort' )}</th>
        {if $priority_sorting}
            <th class="priority">{'Priority'|i18n( 'design/standard/websitetoolbar/sort' )}</th>
        {/if}
    </tr>

    {foreach $children as $child sequence array( 'bglight', 'bgdark' ) as $sequence_style}
    {if $child.can_remove}
        {set $can_remove = true()}
    {/if}
    {def $section_object = fetch( 'section', 'object', hash( 'section_id', $child.object.section_id ) )}

        <tr class="{$sequence_style} ezwt-sort-dragable">
        <td>{$child.name|wash}</td>
        <td class="class">{$child.class_name|wash}</td>
        {if $priority_sorting}
            <td>
            {if node_can_edit}
                <input class="priority ezwt-priority-input" type="text" name="Priority[]" size="3" value="{$child.priority}" title="{'Use the priority fields to control the order in which the items appear. You can use both positive and negative integers. Click the "Update priorities" button to apply the changes.'|i18n( 'design/standard/websitetoolbar/sort' )|wash}" />
                <input type="hidden" name="PriorityID[]" value="{$child.node_id}" />
            {else}
                <input class="priority ezwt-priority-input" type="text" name="Priority[]" size="3" value="{$child.priority}" title="{'You are not allowed to update the priorities because you do not have permission to edit <%node_name>.'|i18n( 'design/standard/websitetoolbar/sort',, hash( '%node_name', $node_name ) )|wash}" disabled="disabled" />
            {/if}
            </td>
        {/if}
      </tr>
    {undef $section_object}
    {/foreach}
</table>

{else}

<div class="block">
    <p>{'The current item does not contain any sub items.'|i18n( 'design/standard/websitetoolbar/sort' )}</p>
</div>

{/if}

<div class="context-toolbar">
{include name=navigator
         uri='design:navigator/alphabetical.tpl'
         page_uri = concat( '/openmagazine/sort/', $node.node_id )
         item_count = $children_count
         view_parameters = $view_parameters
         node_id = $node.node_id
         item_limit = $number_of_items}
</div>

<div class="controlbar">

<div class="block">

    <div class="right">
    {if and( $priority_sorting, $node_can_edit, $children_count )}
        <input id="ezwt-update-priority" class="button" type="submit" name="UpdatePriorityButton" value="{'Update priorities'|i18n( 'design/standard/websitetoolbar/sort' )}" title="{'Apply changes to the priorities of the items in the list above.'|i18n( 'design/standard/websitetoolbar/sort' )}" />
        <input type="hidden" name="RedirectURIAfterPriority" value="{$view_parameters.RedirectURL}" />
        {*<span id="ezwt-automatic-update-container">{'Automatic update'|i18n( 'design/standard/websitetoolbar/sort' )} <input id="ezwt-automatic-update" type="checkbox" name="AutomaticUpdate" value="" /></span>*}
    {else}
        <input id="ezwt-update-priority" class="button-disabled" type="submit" name="UpdatePriorityButton" value="{'Update priorities'|i18n( 'design/standard/websitetoolbar/sort' )}" title="{'You cannot update the priorities because you do not have permission to edit the current item or because a non-priority sorting method is used.'|i18n( 'design/standard/websitetoolbar/sort' )}" disabled="disabled" />
    {/if}
    </div>


<div class="break"></div>

</div>

</div>

</form>

{undef}

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>
