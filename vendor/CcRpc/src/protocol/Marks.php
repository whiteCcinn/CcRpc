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
  const MarkCollection = 'c';      // 集合
  const MarkArgs       = 'a';      // 索引数组 (LIST)
  const MarkMap        = 'm';      // 关联数组 (MAP)

  /* Definition */
  const MarkSemicolon = ';';
  const MarkBodyOpen  = '{';
  const MarkBodyClose = '}';
  const MarkQuote     = '"';

  /* Marks */
  //  const MarkArgument  = 'A';
  const MarkFunctions = 'M';
  const MarkCall      = 'C';
  const MarkResult    = 'R';
  const MarkEnd       = 'q';
  const MarkError     = 'E';
}