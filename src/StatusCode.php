<?php
/**
 * Created by PhpStorm.
 * User: Coa
 * Date: 2/6/2019
 * Time: 2:36 AM
 */

namespace Simple;


interface StatusCode
{
    public const OK = 200;
    public const NO_CONTENT = 204;
    public const BAD_REQUEST = 400;
    public const UNAUTHENTICATED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const INTERNAL = 500;
}