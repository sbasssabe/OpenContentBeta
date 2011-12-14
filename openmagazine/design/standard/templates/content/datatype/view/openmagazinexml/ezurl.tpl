{section show=$attribute.data_text}
{$attribute.content} {$attribute.data_text|washxml}
{section-else}
{$attribute.content}
{/section}