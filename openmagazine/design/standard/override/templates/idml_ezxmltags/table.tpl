<CharacterStyleRange AppliedCharacterStyle="CharacterStyle/$ID/[No character style]">
{set $classification = cond( and(is_set( $align ), $align ), concat( $classification, ' object-', $align ), $classification )}
<table{if $classification} class="{$classification|wash}"{else} class="renderedtable"{/if}>
{$rows}
</table>
</CharacterStyleRange>