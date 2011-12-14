{def $xmltags = ezini( 'ContentTagMatch', 'XMLTag', 'openmagazine.ini' )
     $xmltags_counter = hash()}
{foreach $xmltags as $k => $t}
{set $xmltags_counter = $xmltags_counter|merge( hash( $k, 0 ) )}
{/foreach}
{$attribute.content.output.output_text}
{undef $xmltags $xmltags_counter}

