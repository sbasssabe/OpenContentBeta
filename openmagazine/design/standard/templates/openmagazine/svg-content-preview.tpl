<div id="svg-preview-{$idmlcontent.id}" class="svg-content-preview">
    <p>
        <strong>{'Class Identifier'|i18n( 'extension/openmagazine' )}:</strong> {$idmlnode.class_identifier}<br />
        <strong>{'Attribute Identifier'|i18n( 'extension/openmagazine' )}:</strong> {$idmlattribute.contentclass_attribute_identifier}<br />
        {if is_set( $idmlcontent.xmltag )}
        <strong>{'Xmltag'|i18n( 'extension/openmagazine' )}:</strong> {$idmlcontent.xmltag}<br />
        {/if}
        <strong>{'Text'|i18n( 'extension/openmagazine' )}</strong>:<br />
        <em>{$text}</em>
    </p>
</div>