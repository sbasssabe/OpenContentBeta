{foreach $attribute.content.author_list as $author}
{$author.name|washxml} - {$author.email|washxml}
{/foreach}