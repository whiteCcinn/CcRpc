<?php

/**********************************************************\
 *                                                        *
 * CcRpc/http/Marks.php                                   *
 *                                                        *
 * CcRpc Marks class for php 7.0+                         *
 *                                                        *
 * LastModified: June 23, 2017                            *
 * Author: Cai wenhui <471113744@qq.com>                  *
 *                                                        *
\**********************************************************/

namespace CcRpc\protocol;


class Marks
{
  /* Serialize */
  const MarkInteger    = 'i';
  const MarkLong       = 'l';
  const MarkDouble     = 'd';
  const MarkEmpty      = 'e';
  const MarkNull       = 'n';
  const MarkTrue       = 't';
  const MarkFalse      = 'f';
  const MarkBytes      = 'b';
  const MarkUTF8Char   = 'u';
  const MarkString     = 's';
  const MarkObject     = 'o';
  const MarkCollection = 'c';

  /* Definition */
  const MarkSemicolon = ';';
  const MarkBodyOpen  = '{';
  const MarkBodyClose = '}';
  const MarkQuote     = '"';

  /* Marks */
  const MarkFunctions = 'M';
  const MarkCall      = 'C';
  const MarkResult    = 'R';
  const MarkArgument  = 'A';
  const MarkEnd       = 'q';
}