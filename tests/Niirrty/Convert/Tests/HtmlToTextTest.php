<?php


namespace Niirrty\Convert\Tests;


use Niirrty\Convert\HtmlToText;
use PHPUnit\Framework\TestCase;


class HtmlToTextTest extends TestCase
{


    private HtmlToText $conv1;
    private HtmlToText $conv2;
    private HtmlToText $conv3;
    private HtmlToText $conv4;
    private HtmlToText $conv5;
    private HtmlToText $conv6;

    public function setUp() : void
    {

        parent::setUp();

        $this->conv1 = new HtmlToText(
            "<div class='abc'>A <b>sample text</b> with <i>simple</i> HTML</div>",
            linkStyle: HtmlToText::LINK_STYLE_NONE,
            lineLength: 121
        );
        $this->conv1->convert();
        $this->conv2 = new HtmlToText(
            "<div class='abc'>A <b>sample text</b> with <i>simple</i> HTML</div>",
            linkStyle: HtmlToText::LINK_STYLE_NONE
        );
        $this->conv2->setAllowedElements( [ 'div' ] );
        $this->conv2->convert();
        $this->conv3 = new HtmlToText(
            "A <a href='foo.html'>sample text</a> with a HTML anchor inside",
            linkStyle: HtmlToText::LINK_STYLE_NONE
        );
        $this->conv3->convert();
        $this->conv4 = new HtmlToText(
            "A <a href='foo.html'>sample text</a> with a HTML anchor inside",
            linkStyle: HtmlToText::LINK_STYLE_INLINE
        );
        $this->conv4->convert();
        $this->conv5 = new HtmlToText(
            "A <a href='foo.html'>sample text</a> with <a href='http://foo/'>2 HTML anchors</a> inside",
            linkStyle: HtmlToText::LINK_STYLE_NEXTLINE
        );
        $this->conv5->convert();
        $this->conv6 = new HtmlToText(
            "A <a href='foo.html'>sample text</a> with <a href='http://foo/'>2 HTML anchors</a> inside",
            linkStyle: HtmlToText::LINK_STYLE_TABLE
        );
        $this->conv6->convert();

    }

    public function testConvert()
    {

        $this->assertSame(
            "A SAMPLE TEXT with _simple_ HTML",
            $this->conv1->getResult()
        );
        $this->assertSame(
            "<div>\nA SAMPLE TEXT with _simple_ HTML</div>",
            $this->conv2->getResult()
        );
        $this->assertSame(
            "A sample text with a HTML anchor inside",
            $this->conv3->getResult()
        );
        $this->assertSame(
            "A sample text [/foo.html] with a HTML anchor inside",
            $this->conv4->getResult()
        );
        $this->assertSame(
            "A sample text\n[/foo.html] with 2 HTML anchors\n[http://foo/] inside",
            $this->conv5->getResult()
        );
        $this->assertSame(
            "A sample text [1] with 2 HTML anchors [2] inside\n\nLinks:\n------\n[1] /foo.html\n[2] http://foo/\n",
            $this->conv6->getResult()
        );

    }


}
