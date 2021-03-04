<?php
/**
 * @package Niirrty\Convert
 * @version 0.3.0
 * @since   03.03.2021
 * @author  Ulf Kadner (Xclirion) <ulf.kadner@xclirion.de>
 */


namespace Niirrty\Convert;


use Niirrty\ArgumentException;


/**
 * Class HtmlToText
 *
 * @package Niirrty\Convert
 * @property      string  $baseURL     Contains the base URL that relative links should resolve to.
 * @property      int     $lineLength  The max. length of a resulting text line ( defaults to 70 for mail )
 * @property      string  $linkStyle   How Links should be converted (use one of the LINK_STYLE_* class constants)
 * @property-read bool    $isConverted Gets if the convert process of current source is done.
 */
class HtmlToText implements IHtmlConverter
{


    #region // P R I V A T E   F I E L D S

    /**
     * Contains the base URL that relative links should resolve to.
     *
     * @var string
     */
    private $url;

    /**
     * Contains the HTML content to convert.
     *
     * @var string
     */
    private $html;

    /**
     * Contains the converted, formatted text.
     *
     * @var string
     */
    private $text;

    /**
     * List of preg* regular expression patterns to search for, used in conjunction with $replace.
     *
     * @var array
     */
    private $search = [
        "/\r/",                                  // MAC linebreak carriage return
        "/[\n\t]+/",                             // Newlines and tabs
        '/<head[^>]*>.*?<\/head>/i',             // <head>
        '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
        '/<style[^>]*>.*?<\/style>/i',           // <style>s -- which strip_tags supposedly has problems with
        '/<p[^>]*>/i',                           // <P>
        '/<br[^>]*>/i',                          // <br>
        '/<i[^>]*>(.*?)<\/i>/i',                 // <i>
        '/<em[^>]*>(.*?)<\/em>/i',               // <em>
        '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
        '/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
        '/(<dl[^>]*>|<\/dl>)/i',                 // <dl> and </dl>
        '/<li[^>]*>(.*?)<\/li>/i',               // <li> and </li>
        '/<dd[^>]*>(.*?)<\/dd>/i',               // <dd> and </dd>
        '/<dt[^>]*>(.*?)<\/dt>/i',               // <dt> and </dt>
        '/<li[^>]*>/i',                          // <li>
        '/<hr[^>]*>/i',                          // <hr>
        '/<div[^>]*>/i',                         // <div>
        '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
        '/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
        '/<span class="_html2text_ignore">.+?<\/span>/i'  // <span class="_html2text_ignore">...</span>
    ];

    /**
     * List of pattern replacements corresponding to patterns searched.
     *
     * @var array
     * @see $search
     */
    private $replace = [
        '',                                     // Non-legal carriage return
        ' ',                                    // Newlines and tabs
        '',                                     // <head>
        '',                                     // <script>s -- which strip_tags supposedly has problems with
        '',                                     // <style>s -- which strip_tags supposedly has problems with
        "\n\n",                                 // <P>
        "\n",                                   // <br>
        '_\\1_',                                // <i>
        '_\\1_',                                // <em>
        "\n\n",                                 // <ul> and </ul>
        "\n\n",                                 // <ol> and </ol>
        "\n\n",                                 // <dl> and </dl>
        "\t* \\1\n",                            // <li> and </li>
        " \\1\n",                               // <dd> and </dd>
        "\t* \\1",                              // <dt> and </dt>
        "\n\t* ",                               // <li>
        "\n-------------------------\n",        // <hr>
        "<div>\n",                              // <div>
        "\n\n",                                 // <table> and </table>
        "\n",                                   // <tr> and </tr>
        "\t\t\\1\n",                            // <td> and </td>
        ""                                      // <span class="_html2text_ignore">...</span>
    ];

    /**
     * List of preg* regular expression patterns to search for, used in conjunction with $entitiesReplace.
     *
     * @var array
     */
    private $entitiesSearch = [
        '/&(nbsp|#160);/i',                      // Non-breaking space
        '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
        '/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
        '/&gt;/i',                               // Greater-than
        '/&lt;/i',                               // Less-than
        '/&(copy|#169);/i',                      // Copyright
        '/&(trade|#8482|#153);/i',               // Trademark
        '/&(reg|#174);/i',                       // Registered
        '/&(mdash|#151|#8212);/i',               // mdash
        '/&(ndash|minus|#8211|#8722);/i',        // ndash
        '/&(bull|#149|#8226);/i',                // Bullet
        '/&(pound|#163);/i',                     // Pound sign
        '/&(euro|#8364);/i',                     // Euro sign
        '/&(amp|#38);/i',                        // Ampersand: see _converter()
        '/[ ]{2,}/',                             // Runs of spaces, post-handling
    ];

    /**
     * List of pattern replacements corresponding to patterns searched.
     *
     * @var array
     * @see $ent_search
     */
    private $entitiesReplace = [
        ' ',                                    // Non-breaking space
        '"',                                    // Double quotes
        "'",                                    // Single quotes
        '>',
        '<',
        '(c)',
        '(tm)',
        '(R)',
        '--',
        '-',
        '*',
        '£',
        'EUR',                                  // Euro sign. â‚¬ ?
        '|+|amp|+|',                            // Ampersand: see _converter()
        ' ',                                    // Runs of spaces, post-handling
    ];

    /**
     * List of preg* regular expression patterns to search for and replace using callback function.
     *
     * @var array
     */
    private $callbackSearch = [
        '/<(a) [^>]*href=("|\')([^"\']+)\2([^>]*)>(.*?)<\/a>/i', // <a href="">
        '/<(h)[123456]( [^>]*)?>(.*?)<\/h[123456]>/i',           // h1 - h6
        '/<(b)( [^>]*)?>(.*?)<\/b>/i',                           // <b>
        '/<(strong)( [^>]*)?>(.*?)<\/strong>/i',                 // <strong>
        '/<(th)( [^>]*)?>(.*?)<\/th>/i',                         // <th> and </th>
    ];

    /**
     * List of preg* regular expression patterns to search for in PRE body,
     * used in conjunction with $pre_replace.
     *
     * @var array
     * @see $pre_replace
     */
    private $preSearch = [
        "/\n/",
        "/\t/",
        '/ /',
        '/<pre[^>]*>/',
        '/<\/pre>/'
    ];

    /**
     * List of pattern replacements corresponding to patterns searched for PRE body.
     *
     * @var array
     * @see $pre_search
     */
    private $preReplace = [
        '<br>',
        '&nbsp;&nbsp;&nbsp;&nbsp;',
        '&nbsp;',
        '',
        ''
    ];

    /**
     * Temporary workspace used during PRE processing.
     *
     * @var string
     */
    private $preContent = '';

    /**
     * Contains a list of HTML tags to allow in the resulting text.
     *
     * @var string
     * @see set_allowed_tags()
     */
    private $allowedElements = '';

    /**
     * Indicates whether content in the $html variable has been converted yet.
     *
     * @var bool|null
     */
    private $converted;

    /**
     * Contains URL addresses from links to be rendered in plain text.
     *
     * @var array
     */
    private $linkList = [ ];

    /**
     * Various configuration options (able to be set in the constructor)
     *
     * @var array
     */
    private $options = [
        'linkStyle'  => self::LINK_STYLE_INLINE,
        'lineLength' => 120,
    ];

    #endregion


    #region // C L A S S   C O N S T A N T S

    /**
     * Links from HTML should not be shown (hide it).
     */
    public const LINK_STYLE_NONE = 'none';

    /**
     * Show links from HTML inline the text.
     */
    const LINK_STYLE_INLINE = 'inline';

    /**
     * Show links from HTML but wrap it to a new/next line.
     */
    const LINK_STYLE_NEXTLINE = 'nextline';

    /**
     * The contained HTML links should be shown at the end of the resulting text, table like
     */
    const LINK_STYLE_TABLE = 'table';
    
    protected const ALLOWED_LINK_STYLES = [
        self::LINK_STYLE_NONE,
        self::LINK_STYLE_INLINE,
        self::LINK_STYLE_NEXTLINE,
        self::LINK_STYLE_TABLE
    ];

    #endregion


    #region // C O N S T R U C T O R

    /**
     * Inits a new instance.
     *
     * If the HTML source string (or file) is supplied, the class will instantiate with that source propagated,
     * all that has to be done it to call get_text().
     *
     * @param  string  $source     HTML content
     * @param  bool    $fromFile   Indicates $source is a file path, to pull content from
     * @param  string  $linkStyle  How Links should be converted (use one of the LINK_STYLE_* class constants)
     * @param  int     $lineLength The max. length of a resulting text line ( defaults to 120 for mail )
     */
    public function __construct(
        string $source = '', bool $fromFile = false, string $linkStyle = self::LINK_STYLE_INLINE,
        int $lineLength = 120 )
    {

        $this->converted = null;

        // Sets the options
        $this->options[ 'linkStyle' ]  = \in_array( $linkStyle, static::ALLOWED_LINK_STYLES )
            ? $linkStyle
            : self::LINK_STYLE_INLINE;

        $this->options[ 'lineLength' ] = \intval( $lineLength );
        if ( $this->options[ 'lineLength' ] < 45 )
        {
            $this->options[ 'lineLength' ] = 45;
        }

        if ( ! empty( $source ) )
        {
            $this->setSource( $source, $fromFile );
        }

        $this->setBaseURL();

        $this->converted = false;

    }

    #endregion


    #region // G E T T E R   M E T H O D S

    /**
     * Returns the text, converted from HTML source.
     *
     * @return string
     */
    public function getText() : string
    {

        // Convert, if needed
        if ( ! $this->converted )
        {
            $this->convert();
        }

        return $this->text;

    }

    /**
     * Returns the text, converted from HTML source.
     *
     * @return string
     */
    public function getResult() : string
    {
        return $this->getText();
    }

    /**
     * Magic getter for dynamic property read access.
     *
     * @param  string $name The property name
     * @return mixed Return type is depending to requested Property. If the property is undefined it returns FALSE.
     */
    public function __get( $name )
    {

        if ( isset ( $this->options[ $name ] ) )
        {
            return $this->options[ $name ];
        }

        switch ( \strtolower( $name ) )
        {
            case 'baseurl';
                return $this->url;
            case 'isconverted';
                return null === $this->converted ? false : $this->converted;
            default:
                return false;
        }

    }

    public function getLastState(): ?bool
    {

        return $this->converted;

    }

    #endregion


    #region // S E T T E R   M E T H O D S

    /**
     * Loads source HTML into memory, either from $source string or a file.
     *
     * @param string  $source   The HTML source content
     * @param boolean $fromFile Indicates $source is a file path, to pull content from
     */
    public function setSource( string $source, bool $fromFile = false )
    {

        if ( $fromFile && \file_exists( $source ) )
        {
            $this->html = \file_get_contents( $source );
        }
        else
        {
            $this->html = $source;
        }

        $this->converted = null;

    }

    /**
     * Sets the allowed HTML elements to pass through to the resulting text.
     *
     * Elements should be in the form "&lt;p&gt;&lt;spanp&gt;", or comma separated names "p, span" or a array
     * like array('&lt;p&gt;', '&lt;span&gt;') or array( 'p', 'span' ), with no corresponding closing tag.
     *
     * @param string|array $allowedElements
     */
    public function setAllowedElements( $allowedElements = [] )
    {

        if ( empty( $allowedElements ) )
        {
            $this->allowedElements = '';
            return;
        }

        if ( \is_string( $allowedElements ) )
        {
            $tmp1 = \preg_split( '~(,\s*)~', $allowedElements );
        }
        else if ( \is_array( $allowedElements ) )
        {
            $tmp1 = $allowedElements;
        }
        else
        {
            $this->allowedElements = '';
            return;
        }

        for ( $i = 0; $i < \count( $tmp1 ); ++$i )
        {
            $tmp1[ $i ] = '<' . \trim( $tmp1[ $i ], '<>' ) . '>';
        }

        $this->allowedElements = \join( '', $tmp1 );

    }

    /**
     * Magic setter. Unknown properties throws a exception
     *
     * @param string $name  The name of the dynamic property (caseless)
     * @param mixed  $value The new proerty value.
     * @throws ArgumentException Is thrown if a unknown property is used.
     */
    public function __set( $name, $value )
    {
        switch ( \strtolower( $name ) )
        {
            case 'baseurl':
                $this->setBaseURL( $value );
                break;
            case 'linelength':
                if ( \is_numeric( $value ) )
                {
                    $width = \intval( $value );
                    if ( $width > 45 )
                    {
                        $this->options[ 'lineLength' ] = $width;
                    }
                }
                break;
            case 'linkstyle':
                if ( \in_array( $value, static::ALLOWED_LINK_STYLES ) )
                {
                    $this->options[ 'linkStyle' ] = $value;
                }
                break;
            default:
                throw new ArgumentException( 'name', $name, 'Unknown dynamic property name.' );
        }
    }

    /**
     * Sets a base URL to handle relative links.
     *
     * @param string $url
     */
    public function setBaseURL( string $url = '' )
    {

        if ( empty( $url ) )
        {
            if ( ! empty( $_SERVER[ 'HTTP_HOST' ] ) )
            {
                $this->url = 'http://' . $_SERVER['HTTP_HOST'];
            }
            else
            {
                $this->url = '';
            }
        }
        else
        {
            // Strip any trailing slashes for consistency (relative
            // URLs may already start with a slash like "/file.html")
            if ( '/' === \substr( $url, -1 ) )
            {
                $url = \substr( $url, 0, -1 );
            }
            $this->url = $url;
        }

    }

    #endregion


    #region // O T H E R   P U B L I C   M E T H O D S

    /**
     * Prints the text, converted from HTML, to STDOUT.
     */
    public function printResult()
    {
        print $this->getText();
    }

    /**
     * Workhorse function that does actual conversion.
     */
    public function convert() : bool
    {

        // Variables used for building the link list
        $this->linkList = [];

        $text = \trim( \stripslashes( $this->html ) );

        // Convert HTML to TXT
        $this->doConvert( $text );

        // Add link list
        if ( ! empty( $this->linkList ) )
        {
            $text .= "\n\nLinks:\n------\n";
            foreach ( $this->linkList as $idx => $url )
            {
                $text .= \sprintf( "[%d] %s\n", $idx + 1, $url );
            }
        }

        $this->text = $text;

        $this->converted = true;

        return true;

    }

    #endregion


    #region // P R O T E C T E D   M E T H O D S

    /**
     * Callback function for preg_replace_callback use.
     *
     * @param array $matches PREG matches
     * @return string
     */
    protected function pregCallback( $matches )
    {

        switch ( \strtolower( $matches[ 1 ] ) )
        {

            case 'b':
            case 'strong':
                return $this->toUpper( $matches[ 3 ] );

            case 'th':
                return $this->toUpper( "\t\t" . $matches[ 3 ] . "\n" );

            case 'h':
                return $this->toUpper( "\n\n" . $matches[ 3 ] . "\n\n" );

            case 'a':
                // override the link method
                $linkOverride = null;
                $linkOverrideMatch = null;
                if ( \preg_match( '/_html2text_link_(\w+)/', $matches[ 4 ], $linkOverrideMatch ) )
                {
                    $linkOverride = $linkOverrideMatch[ 1 ];
                }
                // Remove spaces in URL (#1487805)
                $url = \str_replace( ' ', '', $matches[ 3 ] );
                return $this->buildLinkList( $url, $matches[ 5 ], $linkOverride );

        }

        return '';

    }

    /**
     * Callback function for preg_replace_callback use in PRE content handler.
     *
     * @param array $matches PREG matches
     * @return string
     */
    protected function pregPreCallback( /** @noinspection PhpUnusedParameterInspection */ $matches )
    {
        $matches = null;
        return $this->preContent;
    }

    #endregion


    #region // P R I V A T E   M E T H O D S

    /**
     * Workhorse function that does the actual convert.
     *
     * First performs custom tag replacement specified by $search and
     * $replace arrays. Then strips any remaining HTML tags, reduces whitespace
     * and newlines to a readable format, and word wraps the text to
     * $this->options['lineLength'] characters.
     *
     * @param string $text Reference to HTML content string
     */
    private function doConvert( string &$text )
    {

        // Convert <BLOCKQUOTE> (before PRE!)
        $this->convertBlockquotes( $text );

        // Convert <PRE>
        $this->convertPre( $text );

        // Run our defined tags search-and-replace
        $tmp = \preg_replace( $this->search, $this->replace, $text );

        // Run our defined tags search-and-replace with callback
        $tmp = \preg_replace_callback( $this->callbackSearch, array( $this, 'pregCallback' ), $tmp );

        // Strip any other HTML tags
        $tmp = \strip_tags( $tmp, $this->allowedElements );

        // Run our defined entities/characters search-and-replace
        $tmp = \preg_replace( $this->entitiesSearch, $this->entitiesReplace, $tmp );

        // Replace known html entities
        $tmp = \html_entity_decode( $tmp, \ENT_QUOTES );

        // Remove unknown/unhandled entities (this cannot be done in search-and-replace block)
        $tmp = \preg_replace( '/&([a-zA-Z0-9]{2,6}|#[0-9]{2,4});/', '', $tmp );

        // Convert "|+|amp|+|" into "&", need to be done after handling of unknown entities
        // This properly handles situation of "&amp;quot;" in input string
        $tmp = \str_replace( '|+|amp|+|', '&', $tmp );

        // Bring down number of empty lines to 2 max
        $tmp = \preg_replace( "/\n\s+\n/", "\n\n", $tmp );
        $tmp = \preg_replace( "/[\n]{3,}/", "\n\n", $tmp );

        // remove leading empty lines (can be produced by eg. P tag on the beginning)
        $text = \ltrim( $tmp, "\n" );

        // Wrap the text to a readable format
        // for PHP versions >= 4.0.2. Default width is 75
        // If width is 0 or less, don't wrap the text.
        if ( $this->options[ 'lineLength' ] > 0 )
        {
            $text = \wordwrap( $text, $this->options[ 'lineLength' ] );
        }

    }

    /**
     * Helper function called by preg_replace() on link replacement.
     *
     * Maintains an internal list of links to be displayed at the end of the
     * text, with numeric indices to the original point in the text they
     * appeared. Also makes an effort at identifying and handling absolute
     * and relative links.
     *
     * @param  string $link URL of the link
     * @param  string $display Part of the text to associate number with
     * @param  string $link_override
     * @return string
     */
    private function buildLinkList( string $link, string $display, string $link_override = null ) : string
    {

        $link_method = $this->options[ 'linkStyle' ];

        if ( ! empty( $link_override ) && \in_array( $link_override, static::ALLOWED_LINK_STYLES ) )
        {
            $link_method = $link_override;
        }
        if ( $link_method == static::LINK_STYLE_NONE )
        {
            return $display;
        }

        // Ignore js + mailto + anchor link types
        if ( \preg_match( '~^(javascript:|mailto:|#)~i', $link ) )
        {
            return $display;
        }

        if ( \preg_match('~^([a-z][a-z0-9.+-]+:)~i', $link ) )
        {
            // absolute link urls: leave unchanged
            $url = $link;
        }
        else
        {
            $url = $this->url;
            if ( \substr( $link, 0, 1 ) != '/' )
            {
                $url .= '/';
            }
            $url .= $link;
        }

        if ( $link_method === static::LINK_STYLE_TABLE )
        {
            if ( false === ( $index = \array_search( $url, $this->linkList ) ) )
            {
                $index = \count( $this->linkList );
                $this->linkList[] = $url;
            }
            return $display . ' [' . ( $index + 1 ) . ']';
        }
        else if ( $link_method == static::LINK_STYLE_NEXTLINE )
        {
            return $display . "\n[" . $url . ']';
        }
        else
        {
            return $display . ' [' . $url . ']';
        }

    }

    /**
     * Helper function for PRE body conversion.
     *
     * @param string $text HTML content
     */
    private function convertPre( string &$text )
    {

        $matches = null;

        // get the content of PRE element
        while ( \preg_match( '/<pre[^>]*>(.*)<\/pre>/ismU', $text, $matches ) )
        {

            $this->preContent = $matches[ 1 ];

            // Run our defined tags search-and-replace with callback
            $this->preContent = \preg_replace_callback(
                $this->callbackSearch,
                [ $this, 'pregCallback' ],
                $this->preContent
            );

            // convert the content
            $this->preContent = \sprintf(
                '<div><br>%s<br></div>',
                \preg_replace( $this->preSearch, $this->preReplace, $this->preContent )
            );

            // replace the content (use callback because content can contain $0 variable)
            $text = \preg_replace_callback(
                '/<pre[^>]*>.*<\/pre>/ismU',
                [ $this, 'pregPreCallback' ],
                $text,
                1
            );

            // free memory
            $this->preContent = '';

        }

    }

    /**
     * Helper function for BLOCKQUOTE body conversion.
     *
     * @param string $text HTML content
     */
    private function convertBlockquotes( string &$text )
    {

        if ( ! \preg_match_all( '/<\/*blockquote[^>]*>/i', $text, $matches, \PREG_OFFSET_CAPTURE ) )
        {
            return;
        }

        $start  = 0;
        $taglen = 0;
        $level  = 0;
        $diff   = 0;

        foreach ( $matches[ 0 ] as $m )
        {
            if ( $m[ 0 ][ 0 ] == '<' && $m[ 0 ][ 1 ] == '/' )
            {
                $level--;
                if ( $level < 0 )
                {
                    // malformed HTML: go to next blockquote
                    $level = 0;
                }
                else if ( $level === 0 )
                {
                    $end = $m[ 1 ];
                    $len = $end - $taglen - $start;
                    // Get blockquote content
                    $body = \trim( \substr( $text, $start + $taglen - $diff, $len ) );
                    // Set text width
                    $p_width = $this->options[ 'lineLength' ];
                    if ( $this->options[ 'lineLength' ] > 0 )
                    {
                        $this->options[ 'lineLength' ] -= 2;
                    }
                    $this->doConvert( $body );
                    // Add citation markers and create PRE block
                    $bodyNew = '<pre>'
                               . \htmlspecialchars( \preg_replace( '/((^|\n)>*)/', '\\1> ', \trim( $body ) ) )
                               . '</pre>';
                    // Re-set text width
                    $this->options[ 'lineLength' ] = $p_width;
                    // Replace content
                    $text = \substr( $text, 0, $start - $diff )
                            . $bodyNew
                            . \substr( $text, $end + \strlen( $m[ 0 ] ) - $diff );
                    $diff = $len + $taglen + \strlen( $m[ 0 ] ) - \strlen( $bodyNew );
                    unset( $body, $bodyNew );
                }
                // else skip inner blockquote
            }
            else
            {
                if ( $level == 0 )
                {
                    $start  = $m[ 1 ];
                    $taglen = \strlen( $m[ 0 ] );
                }
                $level++;
            }
        }

    }

    /**
     * Strtoupper function with HTML tags and entities handling.
     *
     * @param string $str Text to convert
     * @return string Converted text
     */
    private function toUpper( string $str ) : string
    {

        // string can contain HTML tags
        $chunks = \preg_split( '/(<[^>]*>)/', $str, null, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE );

        // convert to upper only the text between HTML tags
        foreach ( $chunks as $idx => $chunk )
        {
            if ( $chunk[ 0 ] != '<' )
            {
                $chunks[ $idx ] = $this->strToUpper( $chunk );
            }
        }

        return \implode( $chunks );

    }

    /**
     * Strtoupper multibyte wrapper function with HTML entities handling.
     * Forces mbstrToUpper-call to UTF-8.
     *
     * @param string $str Text to convert
     * @return string Converted text
     */
    private function strToUpper( string $str ) : string
    {

        $str = \html_entity_decode( $str, \ENT_COMPAT );

        if ( \function_exists( '\\mb_strtoupper' ) )
        {
            $str = \mb_strtoupper( $str, 'UTF-8' );
        }
        else
        {
            $str = \strtoupper( $str );
        }

        return \htmlspecialchars( $str, \ENT_COMPAT );

    }

    #endregion


}

