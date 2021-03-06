<?php

use CodeIgniter\View\Parser;

class ParserTest extends \CIUnitTestCase
{

    public function setUp()
    {
        $this->loader   = new \CodeIgniter\Autoloader\FileLocator(new \Config\Autoload());
        $this->viewsDir = __DIR__.'/Views';
        $this->config   = new Config\View();
    }

    // --------------------------------------------------------------------

    public function testSetDelimiters()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        // Make sure default delimiters are there
        $this->assertEquals('{', $parser->leftDelimiter);
        $this->assertEquals('}', $parser->rightDelimiter);

        // Change them to square brackets
        $parser->setDelimiters('[', ']');

        // Make sure they changed
        $this->assertEquals('[', $parser->leftDelimiter);
        $this->assertEquals(']', $parser->rightDelimiter);

        // Reset them
        $parser->setDelimiters();

        // Make sure default delimiters are there
        $this->assertEquals('{', $parser->leftDelimiter);
        $this->assertEquals('}', $parser->rightDelimiter);
    }

    // --------------------------------------------------------------------

    public function testParseSimple()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $parser->setVar('teststring', 'Hello World');

        $expected = '<h1>Hello World</h1>';
        $this->assertEquals($expected, $parser->render('template1'));
    }

    // --------------------------------------------------------------------

    public function testParseString()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title' => 'Page Title',
            'body'  => 'Lorem ipsum dolor sit amet.',
        ];

        $template = "{title}\n{body}";

        $result = implode("\n", $data);

        $parser->setData($data);
        $this->assertEquals($result, $parser->renderString($template));
    }

    // --------------------------------------------------------------------

    public function testParseStringMissingData()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title' => 'Page Title',
            'body'  => 'Lorem ipsum dolor sit amet.',
        ];

        $template = "{title}\n{body}\n{name}";

        $result = implode("\n", $data)."\n{name}";

        $parser->setData($data);
        $this->assertEquals($result, $parser->renderString($template));
    }

    // --------------------------------------------------------------------

    public function testParseStringUnusedData()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title' => 'Page Title',
            'body'  => 'Lorem ipsum dolor sit amet.',
            'name'  => 'Someone',
        ];

        $template = "{title}\n{body}";

        $result = "Page Title\nLorem ipsum dolor sit amet.";

        $parser->setData($data);
        $this->assertEquals($result, $parser->renderString($template));
    }

    // --------------------------------------------------------------------

    public function testParseNoTemplate()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $this->assertEquals('', $parser->renderString(''));
    }

    // --------------------------------------------------------------------

    public function testParseArraySingle()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title'  => 'Super Heroes',
            'powers' => [
                ['invisibility' => 'yes', 'flying' => 'no'],
            ],
        ];

        $template = "{ title }\n{ powers }{invisibility}\n{flying}{/powers}";

        $parser->setData($data);
        $this->assertEquals("Super Heroes\nyes\nno", $parser->renderString($template));
    }

    public function testParseArrayMulti()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'powers' => [
                ['invisibility' => 'yes', 'flying' => 'no'],
            ],
        ];

        $template = "{ powers }{invisibility}\n{flying}{/powers}\nsecond:{powers} {invisibility} {flying}{ /powers}";

        $parser->setData($data);
        $this->assertEquals("yes\nno\nsecond: yes no", $parser->renderString($template));
    }

    // --------------------------------------------------------------------

    public function testParseLoop()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title'  => 'Super Heroes',
            'powers' => [
                ['name' => 'Tom'],
                ['name' => 'Dick'],
                ['name' => 'Henry'],
            ],
        ];

        $template = "{title}\n{powers}{name} {/powers}";

        $parser->setData($data);
        $this->assertEquals("Super Heroes\nTom Dick Henry ", $parser->renderString($template));
    }

    // --------------------------------------------------------------------

    public function testMismatchedVarPair()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title'  => 'Super Heroes',
            'powers' => [
                ['invisibility' => 'yes', 'flying' => 'no'],
            ],
        ];

        $template = "{title}\n{powers}{invisibility}\n{flying}";
        $result   = "Super Heroes\n{powers}{invisibility}\n{flying}";

        $parser->setData($data);
        $this->assertEquals($result, $parser->renderString($template));
    }

    // Test anchor

    public function escValueTypes()
    {
        return [
            'scalar'      => [42],
            'string'      => ['George'],
            'scalarlist'  => [[1, 2, 17, -4]],
            'stringlist'  => [['George', 'Paul', 'John', 'Ringo']],
            'associative' => [['name' => 'George', 'role' => 'guitar']],
            'compound'    => [['name' => 'George', 'address' => ['line1' => '123 Some St', 'planet' => 'Naboo']]],
            'pseudo'      => [
                [
                    'name'   => 'George',
                    'emails' => [
                        ['email' => 'me@here.com', 'type' => 'home'],
                        ['email' => 'me@there.com', 'type' => 'work'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider escValueTypes
     */
    public function testEscHandling($value, $expected = null)
    {
        if ($expected == null)
        {
            $expected = $value;
        }
        $this->assertEquals($expected, \esc($value, 'html'));
    }

    //------------------------------------------------------------------------

    /**
     * @see https://github.com/bcit-ci/CodeIgniter4/issues/788
     */
    public function testEscapingRespectsSetDataRaw()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        $template = '{ foo }';

        $parser->setData(['foo' => '<script>'], 'raw');
        $this->assertEquals('<script>', $parser->renderString($template));
    }

    public function testEscapingSetDataWithOtherContext()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        $template = '{ foo }';

        $parser->setData(['foo' => 'http://foo.com'], 'url');
        $this->assertEquals('http%3A%2F%2Ffoo.com', $parser->renderString($template));
    }

    public function testFilterWithNoArgument()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        $data = [
            'that_thing' => '<script>alert("ci4")</script>'
        ];

        $template = '{ that_thing|esc }';

        $parser->setData($data);
        $this->assertEquals('&lt;script&gt;alert(&quot;ci4&quot;)&lt;/script&gt;', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testFilterWithArgument()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        $date = time();

        $data = [
            'my_date' => $date
        ];

        $template = '{ my_date| date(Y-m-d ) }';

        $parser->setData($data);
        $this->assertEquals(date('Y-m-d', $date), $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testParserEscapesDataDefaultsToHTML()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        $data = [
            'title' => '<script>Heroes</script>',
            'powers' => [
                ['link' => "<a href='test'>Link</a>"],
            ],
        ];

        $template = "{title} {powers}{link}{/powers}";
        $parser->setData($data);
        $this->assertEquals('&lt;script&gt;Heroes&lt;/script&gt; &lt;a href=&#039;test&#039;&gt;Link&lt;/a&gt;', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testParserNoEscape()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        $data = [
            'title' => '<script>Heroes</script>',
        ];

        $template = "{! title!}";
        $parser->setData($data);
        $this->assertEquals('<script>Heroes</script>', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testIgnoresComments()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title'  => 'Super Heroes',
            'powers' => [
                ['invisibility' => 'yes', 'flying' => 'no'],
            ],
        ];

        $template = "{# Comments #}{title}\n{powers}{invisibility}\n{flying}";
        $result   = "Super Heroes\n{powers}{invisibility}\n{flying}";

        $parser->setData($data);
        $this->assertEquals($result, $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testNoParse()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title'  => 'Super Heroes',
            'powers' => [
                ['invisibility' => 'yes', 'flying' => 'no'],
            ],
        ];

        $template = "{noparse}{title}\n{powers}{invisibility}\n{flying}{/noparse}";
        $result   = "{title}\n{powers}{invisibility}\n{flying}";

        $parser->setData($data);
        $this->assertEquals($result, $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testIfConditionalTrue()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'doit' => true,
            'dontdoit' => false
        ];

        $template = "{if doit}Howdy{endif}{ if dontdoit === false}Welcome{ endif }";
        $parser->setData($data);

        $this->assertEquals('HowdyWelcome', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testElseConditionalFalse()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'doit' => true,
        ];

        $template = "{if doit}Howdy{else}Welcome{ endif }";
        $parser->setData($data);

        $this->assertEquals('Howdy', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testElseConditionalTrue()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'doit' => false,
        ];

        $template = "{if doit}Howdy{else}Welcome{ endif }";
        $parser->setData($data);

        $this->assertEquals('Welcome', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testElseifConditionalTrue()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'doit' => false,
            'dontdoit' => true
        ];

        $template = "{if doit}Howdy{elseif dontdoit}Welcome{ endif }";
        $parser->setData($data);

        $this->assertEquals('Welcome', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testWontParsePHP()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        $template = "<?php echo 'Foo' ?> - <?= 'Bar' ?>";
        $this->assertEquals('&lt;?php echo \'Foo\' ?&gt; - &lt;?= \'Bar\' ?&gt;', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    public function testParseHandlesSpaces()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title' => 'Page Title',
            'body'  => 'Lorem ipsum dolor sit amet.',
        ];

        $template = "{ title}\n{ body }";

        $result = implode("\n", $data);

        $parser->setData($data);
        $this->assertEquals($result, $parser->renderString($template));
    }

    // --------------------------------------------------------------------

    public function testParseRuns()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'title' => 'Page Title',
            'body'  => 'Lorem ipsum dolor sit amet.',
        ];

        $template = "{ title}\n{ body }";

        $result = implode("\n", $data);

        $parser->setData($data);
        $this->assertEquals($result, $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    /**
     * @group parserplugins
     */
    public function testCanAddAndRemovePlugins()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $parser->addPlugin('first', function($str){ return $str; });

        $setParsers = $this->getPrivateProperty($parser, 'plugins');

        $this->assertArrayHasKey('first', $setParsers);

        $parser->removePlugin('first');

        $setParsers = $this->getPrivateProperty($parser, 'plugins');

        $this->assertArrayNotHasKey('first', $setParsers);
    }

    //--------------------------------------------------------------------

    /**
     * @group parserplugins
     */
    public function testParserPluginNoMatches()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);

        $template = "hit:it";

        $this->assertEquals("hit:it", $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    /**
     * @group parserplugins
     */
    public function testParserPluginNoParams()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $parser->addPlugin('hit:it', function($str){
            return str_replace('here', "Hip to the Hop", $str);
        }, true);

        $template = "{+ hit:it +} stuff here {+ /hit:it +}";

        $this->assertEquals(" stuff Hip to the Hop ", $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    /**
     * @group parserplugins
     */
    public function testParserPluginParams()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $parser->addPlugin('growth', function($str, array $params){
            $step = $params['step'] ?? 1;
            $count = $params['count'] ?? 2;

            $out = '';

            for ($i=1; $i <= $count; $i++)
            {
                $out .= " ".$i * $step;
            }

            return $out;
        }, true);

        $template = "{+ growth step=2 count=4 +}  {+ /growth +}";

        $this->assertEquals(" 2 4 6 8", $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    /**
     * @group parserplugins
     */
    public function testParserSingleTag()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $parser->addPlugin('hit:it', function(){
            return "Hip to the Hop";
        }, false);

        $template = "{+ hit:it +}";

        $this->assertEquals("Hip to the Hop", $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    /**
     * @group parserplugins
     */
    public function testParserSingleTagWithParams()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $parser->addPlugin('hit:it', function(array $params=[]){
            return "{$params['first']} to the {$params['last']}";
        }, false);

        $template = "{+ hit:it first=foo last=bar +}";

        $this->assertEquals("foo to the bar", $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    /**
     * @group parserplugins
     */
    public function testParserSingleTagWithSingleParams()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $parser->addPlugin('hit:it', function(array $params=[]){
            return "{$params[0]} to the {$params[1]}";
        }, false);

        $template = "{+ hit:it foo bar +}";

        $this->assertEquals("foo to the bar", $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    /**
     * @group parserplugins
     */
    public function testParserSingleTagWithQuotedParams()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $parser->addPlugin('count', function(array $params=[]){
            $out = '';

            foreach ($params as $index => $param)
            {
                $out .= "{$index}. {$param} ";
            }

            return $out;
        }, false);

        $template = '{+ count "foo bar" baz "foo bar" +}';

        $this->assertEquals('0. foo bar 1. baz 2. foo bar ', $parser->renderString($template));
    }

    //--------------------------------------------------------------------

    /**
     * @see https://github.com/bcit-ci/CodeIgniter4/issues/705
     */
    public function testParseLoopWithDollarSign()
    {
        $parser = new Parser($this->config, $this->viewsDir, $this->loader);
        $data   = [
            'books' => [
                ['price' => '12.50'],
            ],
        ];

        $template = "{books}<p>Price $: {price}</p>{/books}";

        $parser->setData($data);
        $this->assertEquals("<p>Price $: 12.50</p>", $parser->renderString($template));
    }

    // --------------------------------------------------------------------
}
