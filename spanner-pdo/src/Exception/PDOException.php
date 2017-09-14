<?php
/**
 * This file is part of Spanner-PDO for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
namespace SpannerPDO\Sql\Exception;

use PDOException as BasePDOException;

class PDOException extends BasePDOException implements ExceptionInterface
{
}
