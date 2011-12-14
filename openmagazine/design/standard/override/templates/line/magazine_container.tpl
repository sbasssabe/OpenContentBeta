{* Folder - Line view *}

<div class="content-view-line">
    <div class="class-magazine_container">

        <h2>
            <small> {$node.object.published|l10n(shortdate)} - </small>
            <a href={$node.url_alias|ezurl}>{$node.name|wash()}</a>
            {if $node.data_map.magazine_status.data_int}
            <small> - <strong>{$node.data_map.magazine_status.contentclass_attribute.name}</strong></small>
            {/if}
        </h2>

    </div>
</div>
