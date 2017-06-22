<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/22
 * Time: 17:12
 */

namespace CcRpc\protocol;


class Mark
{
  /* Serialize */
  const MarkInteger  = 'i';
  const MarkLong     = 'l';
  const MarkDouble   = 'd';
  const MarkNull     = 'n';
  const MarkTrue     = 't';
  const MarkFalse    = 'f';
  const MarkBytes    = 'b';
  const MarkUTF8Char = 'u';
  const MarkString   = 's';
  const MarkClass    = 'c';

  /* Definition */
  const MarkBodyOpen  = '{';
  const MarkBodyClose = '}';

  /* Marks */
  const MarkFunctions = 'F';
  const MarkCall      = 'C';
  const MarkResult    = 'R';
  const MarkArgument  = 'A';
  const MarkEnd       = 'q';
}