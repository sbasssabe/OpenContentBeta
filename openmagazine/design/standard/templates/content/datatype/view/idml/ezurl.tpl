{section show=$attribute.data_text}
{$attribute.data_text|washxml}
{section-else}
{$attribute.content|washxml}
{/section}
