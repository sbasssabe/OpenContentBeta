{let matrix=$attribute.content}
<aid:table xmlns:aid="http://ns.adobe.com/AdobeInDesign/3.0/">
<aid:tbody>
<aid:tr>
{section var=ColumnNames loop=$matrix.columns.sequential}
<aid:td>{$ColumnNames.item.name}</aid:td>
{/section}
</aid:tr>
{section var=Rows loop=$matrix.rows.sequential}
</aid:tr>
    {section var=Columns loop=$Rows.item.columns}
    <aid:td>{$Columns.item|washxml}</aid:td>
    {/section}
</aid:tr>
{/section}
</aid:table>
{/let}