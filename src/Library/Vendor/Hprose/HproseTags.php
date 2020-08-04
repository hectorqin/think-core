<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.net/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * HproseTags.php                                         *
 *                                                        *
 * hprose tags library for php5.                          *
 *                                                        *
 * LastModified: Nov 10, 2010                             *
 * Author: Ma Bingyao <andot@hprfc.com>                   *
 *                                                        *
\**********************************************************/

class HproseTags
{
    /* Serialize Tags */
    const TAGINTEGER  = 'i';
    const TAGLONG     = 'l';
    const TAGDOUBLE   = 'd';
    const TAGNULL     = 'n';
    const TAGEMPTY    = 'e';
    const TAGTRUE     = 't';
    const TAGFALSE    = 'f';
    const TAGNAN      = 'N';
    const TAGINFINITY = 'I';
    const TAGDATE     = 'D';
    const TAGTIME     = 'T';
    const TAGUTC      = 'Z';
    const TAGBYTES    = 'b';
    const TAGUTF8CHAR = 'u';
    const TAGSTRING   = 's';
    const TAGGUID     = 'g';
    const TAGLIST     = 'a';
    const TAGMAP      = 'm';
    const TAGCLASS    = 'c';
    const TAGOBJECT   = 'o';
    const TAGREF      = 'r';
    /* Serialize Marks */
    const TAGPOS        = '+';
    const TAGNEG        = '-';
    const TAGSEMICOLON  = ';';
    const TAGOPENBRACE  = '{';
    const TAGCLOSEBRACE = '}';
    const TAGQUOTE      = '"';
    const TAGPOINT      = '.';
    /* Protocol Tags */
    const TAGFUNCTIONS = 'F';
    const TAGCALL      = 'C';
    const TAGRESULT    = 'R';
    const TAGARGUMENT  = 'A';
    const TAGERROR     = 'E';
    const TAGEND       = 'z';
}
