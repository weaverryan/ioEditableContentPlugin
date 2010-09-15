<?php

// test the process of viewing and saving editable areas
require_once dirname(__FILE__).'/../bootstrap/functional.php';

$browser = new sfTestFunctional(new sfBrowser());
$browser->setTester('doctrine', 'sfTesterDoctrine');

Doctrine_Query::create()->from('Blog')->delete()->execute();
$blog = new Blog();
$blog->title = '';
$blog->body = 'Lorem ipsum';
$blog->save();

$browser->info('1 - Visit page, but not logged in')
  ->info('  1.1 - Start by being logged out.')
  ->get('/blog')

  ->with('request')->begin()
    ->isParameter('module', 'test')
    ->isParameter('action', 'blog')
  ->end()

  ->with('response')->begin()
    ->info('  1.2 - The title should be empty - we are not logged in, so no default text for the empty title field.')
    ->checkElement('.test_title h1', '')
    ->info('  1.3 - The title tag should not have the editable class, or any extra markup')
    ->checkElement('.test_title h1[class="test_editable_class_name"]', false)
  ->end()
;

$context = $browser->getContext(true);
$context->getUser()->setAuthenticated(true);
$context->getUser()->addCredential('test_credential');
$context->getUser()->shutdown();
$context->getStorage()->shutdown();

$browser->info('2 - Goto a page, now logged in with the correct credential')
  ->get('/blog')

  ->with('response')->begin()
    ->isStatusCode(200)
    ->info('  2.1 - The title has the configured placeholder text')
    ->checkElement('.test_title h1', '[Test edit]')
    ->info('  2.2 - Check for the markup for the editor - should be present now')
    ->checkElement('.test_title h1.test_editable_class_name', 1)
    ->checkElement('.test_body div div:last', 'Lorem ipsum')
  ->end()
;

$blog->title = 'test blog';
$blog->save();

$form = new BlogForm($blog);
$form->useFields(array('title'));
$browser->info('3 - Display and submit a simple form')
  ->get('/service/content/form?model=Blog&pk=2&fields[]=title')

  ->with('request')->begin()
    ->isParameter('module', 'ioEditableContent')
    ->isParameter('action', 'form')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
    ->checkForm($form)
    ->checkElement('input[type=hidden][name=model][value=Blog]', 1)
    ->checkElement('input[type=hidden][name=pk][value='.$blog->id.']', 1)
    ->checkElement('input[type=hidden][name=form][value="BlogForm"]', 1)
    ->checkElement('input[type=hidden][name=form_partial][value=ioEditableContent/formFields]', 1)
    ->checkElement('input[type=hidden][name=partial][value=]', 1)
    ->checkElement('input[type=hidden][name="method"][value=]', 1)
    ->checkElement('input[type=hidden][name="fields[]"][value=title]', 1)
  ->end()

  ->info('  3.1 - Submit with errors')
  ->click('save', array('blog' => array(
    'fake_field' => 'val',
  )))

  ->with('request')->begin()
    ->isParameter('module', 'ioEditableContent')
    ->isParameter('action', 'update')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
  ->end()
;

$browser->info('  3.2 - check the json response');
$response = $browser->getResponse()->getContent();
$json = json_decode($response);
$browser->test()->is($json->error, 'There were 1 errors when submitting the form.', 'The ->error key comes back correctly');
$browser->test()->like($json->response, '/id\=\"blog_title\"/', 'The ->respones key contains the re-rendered form fields');
$browser->test()->like($json->response, '/Unexpected extra form field named \"fake_field\"/', '->respones contains the global errors');

$browser
  ->get('/service/content/form?model=Blog&pk=2&fields[]=title')
  ->info('  3.3 - Submit a valid form')
  ->click('save', array('blog' => array(
    'title' => 'new title',
  )))

  ->with('doctrine')->begin()
    ->check('Blog', array('title' => 'new title'), 1)
  ->end()
;
$browser->info('  3.4 - check the json response');
$response = $browser->getResponse()->getContent();
$json = json_decode($response);
$browser->test()->is($json->error, '', '->error is blank because the form submitted successfully');
$browser->test()->like($json->response, '/id\=\"blog_title\"/', 'The ->respones key contains the re-rendered form fields');

$browser->info('  3.5 - Goto the show page for this content area')
  ->get('/service/content/show?model=Blog&pk=2&fields%5B%5D=title')

  ->with('request')->begin()
    ->isParameter('module', 'ioEditableContent')
    ->isParameter('action', 'show')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
    ->matches('/new title/')
  ->end()
;



$form = new BlogBodyForm($blog);
$browser->info('4 - Display and submit a complex form')
  ->get('/service/content/form?model=Blog&pk=2&form=BlogBodyForm&form_partial=test%2FbodyForm&partial=test%2Fbody&method=getTestValue')

  ->with('request')->begin()
    ->isParameter('module', 'ioEditableContent')
    ->isParameter('action', 'form')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
    ->checkForm($form)
    ->checkElement('input[type=hidden][name=model][value=Blog]', 1)
    ->checkElement('input[type=hidden][name=pk][value='.$blog->id.']', 1)
    ->checkElement('input[type=hidden][name=form][value="BlogBodyForm"]', 1)
    ->checkElement('input[type=hidden][name=form_partial][value=test/bodyForm]', 1)
    ->checkElement('input[type=hidden][name="method"][value=getTestValue]', 1)
    ->checkElement('input[type=hidden][name=partial][value=test/body]', 1)
  ->end()

  ->info('  4.1 - Submit a valid form')
  ->click('save', array('blog' => array(
    'body' => 'new body',
  )))

  ->with('response')->begin()
    ->isStatusCode('200')
  ->end()

  ->with('doctrine')->begin()
    ->check('Blog', array('body' => 'new body'), 1)
  ->end()
;
$browser->info('  4.2 - check the json response');
$response = $browser->getResponse()->getContent();
$json = json_decode($response);
$browser->test()->is($json->error, '', '->error is blank because the form submitted successfully');
$browser->test()->like($json->response, '/id\=\"blog_body\"/', 'The ->respones key contains the re-rendered form fields');

$browser->info('  4.3 - Goto the show page for this content area')
  ->get('/service/content/show?model=Blog&pk=2&form=BlogBodyForm&form_partial=test%2FbodyForm&partial=test%2Fbody')

  ->with('request')->begin()
    ->isParameter('module', 'ioEditableContent')
    ->isParameter('action', 'show')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
    ->matches('/new body/')
  ->end()

  ->info('  4.4 - Goto the show page for content driven by the "method" option')
  ->get('/service/content/show?model=Blog&pk=2&form=BlogBodyForm&form_partial=test%2FbodyForm&method=getTestValue')

  ->with('request')->begin()
    ->isParameter('module', 'ioEditableContent')
    ->isParameter('action', 'show')
  ->end()

  ->with('response')->begin()
    ->isStatusCode(200)
    ->matches('/unit_test_value/')
  ->end()
;


Doctrine_Query::create()->from('Blog')->delete()->execute();
$browser->info('5 - Fill out a form with a new object')
  ->get('/service/content/form?model=Blog&pk=null&fields[]=title')

  ->with('response')->begin()
    ->isStatusCode(200)
  ->end()

  ->click('save', array('blog' => array(
    'title' => 'new blog post',
  )))

  ->with('doctrine')->begin()
    ->check('Blog', array('title' => 'new blog post'), 1)
  ->end()
;

$blog = Doctrine_Query::create()->from('Blog')->fetchOne();
$browser->info('  5.1 - check the json response for the pk key');
$response = $browser->getResponse()->getContent();
$json = json_decode($response);
$browser->test()->is($json->pk, $blog->id, '->pk is the id of the new Blog entry');

$html = $json->response;
$crawler = new Crawler($html);
$value = $crawler->filterXPath('//input[@name="pk"]')->attr('value');
$browser->test()->is($value, $blog->id, 'pk hidden input is the id of the blog entry');








/**
 * Crawler eases navigation of a list of \DOMNode objects.
 *
 * @package    Symfony
 * @subpackage Components_DomCrawler
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Crawler extends \SplObjectStorage
{
    protected $uri;
    protected $host;
    protected $path;

    /**
     * Constructor.
     *
     * @param mixed  $node A Node to use as the base for the crawling
     * @param string $uri  The base URI to use for absolute links or form actions
     */
    public function __construct($node = null, $uri = null)
    {
        $this->uri = $uri;
        list($this->host, $this->path) = $this->parseUri($this->uri);

        $this->add($node);
    }

    /**
     * Removes all the nodes.
     */
    public function clear()
    {
        $this->removeAll($this);
    }

    /**
     * Adds a node to the current list of nodes.
     *
     * This method uses the appropriate specialized add*() method based
     * on the type of the argument.
     *
     * @param null|\DOMNodeList|array|\DOMNode $node A node
     */
    public function add($node)
    {
        if ($node instanceof \DOMNodeList) {
            $this->addNodeList($node);
        } elseif (is_array($node)) {
            $this->addNodes($node);
        } elseif (is_string($node)) {
            $this->addContent($node);
        } elseif (is_object($node)) {
            $this->addNode($node);
        }
    }

    public function addContent($content, $type = null)
    {
        if (empty($type)) {
            $type = 'text/html';
        }

        // DOM only for HTML/XML content
        if (!preg_match('/(x|ht)ml/i', $type, $matches)) {
            return null;
        }

        $charset = 'ISO-8859-1';
        if (false !== $pos = strpos($type, 'charset=')) {
            $charset = substr($type, $pos + 8);
        }

        if ('x' === $matches[1]) {
            $this->addXmlContent($content, $charset);
        } else {
            $this->addHtmlContent($content, $charset);
        }
    }

    /**
     * Adds an HTML content to the list of nodes.
     *
     * @param string $content The HTML content
     * @param string $charset The charset
     */
    public function addHtmlContent($content, $charset = 'UTF-8')
    {
        $dom = new \DOMDocument('1.0', $charset);
        $dom->validateOnParse = true;

        @$dom->loadHTML($content);
        $this->addDocument($dom);
    }

    /**
     * Adds an XML content to the list of nodes.
     *
     * @param string $content The XML content
     * @param string $charset The charset
     */
    public function addXmlContent($content, $charset = 'UTF-8')
    {
        $dom = new \DOMDocument('1.0', $charset);
        $dom->validateOnParse = true;

        // remove the default namespace to make XPath expressions simpler
        @$dom->loadXML(str_replace('xmlns', 'ns', $content));
        $this->addDocument($dom);
    }

    /**
     * Adds a \DOMDocument to the list of nodes.
     *
     * @param \DOMDocument $dom A \DOMDocument instance
     */
    public function addDocument(\DOMDocument $dom)
    {
        if ($dom->documentElement) {
            $this->addNode($dom->documentElement);
        }
    }

    /**
     * Adds a \DOMNodeList to the list of nodes.
     *
     * @param \DOMNodeList $nodes A \DOMNodeList instance
     */
    public function addNodeList(\DOMNodeList $nodes)
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
    }

    /**
     * Adds an array of \DOMNode instances to the list of nodes.
     *
     * @param array $nodes An array of \DOMNode instances
     */
    public function addNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            $this->add($node);
        }
    }

    /**
     * Adds a \DOMNode instance to the list of nodes.
     *
     * @param \DOMNode $node A \DOMNode instance
     */
    public function addNode(\DOMNode $node)
    {
        if ($node instanceof \DOMDocument) {
            $this->attach($node->documentElement);
        } else {
            $this->attach($node);
        }
    }

    /**
     * Returns true if the list of nodes is empty.
     *
     * @return Boolean true if the list of nodes is empty, false otherwise
     */
    public function isEmpty()
    {
        return $this->count() < 1;
    }

    /**
     * Returns a node given its position in the node list.
     *
     * @param integer $position The position
     *
     * @return A new instance of the Crawler with the selected node, or an empty Crawler if it does not exist.
     */
    public function eq($position)
    {
        foreach ($this as $i => $node) {
            if ($i == $position) {
                return new static($node, $this->uri);
            }
        }

        return new static(null, $this->uri);
    }

    /**
     * Calls an anonymous function on each node of the list.
     *
     * The anonymous function receives the position and the node as arguments.
     *
     * Example:
     *
     *     $crawler->filter('h1')->each(function ($i, $node)
     *     {
     *       return $node->nodeValue;
     *     });
     *
     * @param \Closure $closure An anonymous function
     *
     * @return array An array of values returned by the anonymous function
     */
    public function each(\Closure $closure)
    {
        $data = array();
        foreach ($this as $i => $node) {
            $data[] = $closure($node, $i);
        }

        return $data;
    }

    /**
     * Reduces the list of nodes by calling an anonymous function.
     *
     * To remove a node from the list, the anonymous function must return false.
     *
     * @param \Closure $closure An anonymous function
     *
     * @param Crawler A Crawler instance with the selected nodes.
     */
    public function reduce(\Closure $closure)
    {
        $nodes = array();
        foreach ($this as $i => $node) {
            if (false !== $closure($node, $i)) {
                $nodes[] = $node;
            }
        }

        return new static($nodes, $this->uri);
    }

    /**
     * Returns the first node of the current selection
     *
     * @return Crawler A Crawler instance with the first selected node
     */
    public function first()
    {
        return $this->eq(0);
    }

    /**
     * Returns the last node of the current selection
     *
     * @return Crawler A Crawler instance with the last selected node
     */
    public function last()
    {
        return $this->eq($this->count() - 1);
    }

    /**
     * Returns the siblings nodes of the current selection
     *
     * @return Crawler A Crawler instance with the sibling nodes
     *
     * @throws \InvalidArgumentException When current node is empty
     */
    public function siblings()
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        return new static($this->sibling($this->getNode(0)->parentNode->firstChild), $this->uri);
    }

    /**
     * Returns the next siblings nodes of the current selection
     *
     * @return Crawler A Crawler instance with the next sibling nodes
     *
     * @throws \InvalidArgumentException When current node is empty
     */
    public function nextAll()
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        return new static($this->sibling($this->getNode(0)), $this->uri);
    }

    /**
     * Returns the previous sibling nodes of the current selection
     *
     * @return Crawler A Crawler instance with the previous sibling nodes
     */
    public function previousAll()
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        return new static($this->sibling($this->getNode(0), 'previousSibling'), $this->uri);
    }

    /**
     * Returns the parents nodes of the current selection
     *
     * @return Crawler A Crawler instance with the parents nodes of the current selection
     *
     * @throws \InvalidArgumentException When current node is empty
     */
    public function parents()
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        $node = $this->getNode(0);
        $nodes = array();

        while ($node = $node->parentNode) {
            if (1 === $node->nodeType && '_root' !== $node->nodeName) {
                $nodes[] = $node;
            }
        }

        return new static($nodes, $this->uri);
    }

    /**
     * Returns the children nodes of the current selection
     *
     * @return Crawler A Crawler instance with the children nodes
     *
     * @throws \InvalidArgumentException When current node is empty
     */
    public function children()
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        return new static($this->sibling($this->getNode(0)->firstChild), $this->uri);
    }

    /**
     * Returns the attribute value of the first node of the list.
     *
     * @param string $attribute The attribute name
     *
     * @return string The attribute value
     *
     * @throws \InvalidArgumentException When current node is empty
     */
    public function attr($attribute)
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        return $this->getNode(0)->getAttribute($attribute);
    }

    /**
     * Returns the node value of the first node of the list.
     *
     * @return string The node value
     *
     * @throws \InvalidArgumentException When current node is empty
     */
    public function text()
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        return $this->getNode(0)->nodeValue;
    }

    /**
     * Extracts information from the list of nodes.
     *
     * You can extract attributes or/and the node value (_text).
     *
     * Example:
     *
     * $crawler->filter('h1 a')->extract(array('_text', 'href'));
     *
     * @param array $attributes An array of attributes
     *
     * @param array An array of extracted values
     */
    public function extract($attributes)
    {
        if (!is_array($attributes)) {
            $attributes = array($attributes);
        }

        $data = array();
        foreach ($this as $node) {
            $elements = array();
            foreach ($attributes as $attribute) {
                if ('_text' === $attribute) {
                    $elements[] = $node->nodeValue;
                } else {
                    $elements[] = $node->getAttribute($attribute);
                }
            }

            $data[] = count($attributes) > 1 ? $elements : $elements[0];
        }

        return $data;
    }

    /**
     * Filters the list of nodes with an XPath expression.
     *
     * @param string $xpath An XPath expression
     *
     * @return Crawler A new instance of Crawler with the filtered list of nodes
     */
    public function filterXPath($xpath)
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $root = $document->appendChild($document->createElement('_root'));
        foreach ($this as $node) {
            $root->appendChild($document->importNode($node, true));
        }

        $domxpath = new \DOMXPath($document);

        return new static($domxpath->query($xpath), $this->uri);
    }

    /**
     * Filters the list of nodes with a CSS selector.
     *
     * This method only works if you have installed the CssSelector Symfony Component.
     *
     * @param string $selector A CSS selector
     *
     * @return Crawler A new instance of Crawler with the filtered list of nodes
     *
     * @throws \RuntimeException if the CssSelector Component is not available
     */
    public function filter($selector)
    {
        if (!class_exists('Symfony\\Components\\CssSelector\\Parser')) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Unable to filter with a CSS selector as the Symfony CssSelector is not installed (you can use filterXPath instead).');
            // @codeCoverageIgnoreEnd
        }

        return $this->filterXPath(CssParser::cssToXpath($selector));
    }

    /**
     * Selects links by name or alt value for clickable images.
     *
     * @param  string $value The link text
     *
     * @return Crawler A new instance of Crawler with the filtered list of nodes
     */
    public function selectLink($value)
    {
        $xpath  = sprintf('//a[contains(concat(\' \', normalize-space(string(.)), \' \'), %s)] ', static::xpathLiteral(' '.$value.' ')).
                            sprintf('| //a/img[contains(concat(\' \', normalize-space(string(@alt)), \' \'), %s)]/ancestor::a', static::xpathLiteral(' '.$value.' '));

        return $this->filterXPath($xpath);
    }

    /**
     * Selects a button by name or alt value for images.
     *
     * @param  string $value The button text
     *
     * @return Crawler A new instance of Crawler with the filtered list of nodes
     */
    public function selectButton($value)
    {
        $xpath = sprintf('//input[((@type="submit" or @type="button") and contains(concat(\' \', normalize-space(string(@value)), \' \'), %s)) ', static::xpathLiteral(' '.$value.' ')).
                         sprintf('or (@type="image" and contains(concat(\' \', normalize-space(string(@alt)), \' \'), %s)) or @id="%s" or @name="%s"] ', static::xpathLiteral(' '.$value.' '), $value, $value).
                         sprintf('| //button[contains(concat(\' \', normalize-space(string(.)), \' \'), %s) or @id="%s" or @name="%s"]', static::xpathLiteral(' '.$value.' '), $value, $value);

        return $this->filterXPath($xpath);
    }

    /**
     * Returns a Link object for the first node in the list.
     *
     * @param  string $method The method for the link (get by default)
     *
     * @return Link   A Link instance
     *
     * @throws \InvalidArgumentException If the current node list is empty
     */
    public function link($method = 'get')
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        $node = $this->getNode(0);

        return new Link($node, $method, $this->host, $this->path);
    }

    /**
     * Returns an array of Link objects for the nodes in the list.
     *
     * @return array An array of Link instances
     */
    public function links()
    {
        $links = array();
        foreach ($this as $node) {
            $links[] = new Link($node, 'get', $this->host, $this->path);
        }

        return $links;
    }

    /**
     * Returns a Form object for the first node in the list.
     *
     * @param  array  $arguments An array of values for the form fields
     * @param  string $method    The method for the form
     *
     * @return Form   A Form instance
     *
     * @throws \InvalidArgumentException If the current node list is empty
     */
    public function form(array $values = null, $method = null)
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        $form = new Form($this->getNode(0), $method, $this->host, $this->path);

        if (null !== $values) {
            $form->setValues($values);
        }

        return $form;
    }

    protected function getNode($position)
    {
        foreach ($this as $i => $node) {
            if ($i == $position) {
                return $node;
            }
        // @codeCoverageIgnoreStart
        }

        return null;
        // @codeCoverageIgnoreEnd
    }

    protected function parseUri($uri)
    {
        if ('http' !== substr($uri, 0, 4)) {
            return array(null, '/');
        }

        $path = parse_url($uri, PHP_URL_PATH);

        if ('/' !== substr($path, -1)) {
            $path = substr($path, 0, strrpos($path, '/') + 1);
        }

        return array(preg_replace('#^(.*?//[^/]+)\/.*$#', '$1', $uri), $path);
    }

    protected function sibling($node, $siblingDir = 'nextSibling')
    {
        $nodes = array();

        do {
            if ($node !== $this->getNode(0) && $node->nodeType === 1) {
                $nodes[] = $node;
            }
        } while($node = $node->$siblingDir);

        return $nodes;
    }

    static public function xpathLiteral($s)
    {
        if (false === strpos($s, "'")) {
            return sprintf("'%s'", $s);
        }

        if (false === strpos($s, '"')) {
            return sprintf('"%s"', $s);
        }

        $string = $s;
        $parts = array();
        while (true) {
            if (false !== $pos = strpos($string, "'")) {
                $parts[] = sprintf("'%s'", substr($string, 0, $pos));
                $parts[] = "\"'\"";
                $string = substr($string, $pos + 1);
            } else {
                $parts[] = "'$string'";
                break;
            }
        }

        return sprintf("concat(%s)", implode($parts, ', '));
    }
}

