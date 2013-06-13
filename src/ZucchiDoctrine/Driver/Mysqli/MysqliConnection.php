<?php
/**
 * MysqliConnection (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */

namespace ZucchiDoctrine\Driver\Mysqli;

use Doctrine\DBAL\Driver\Connection as Connection;

/**
 * MysqliConnection
 *
 * @author Rick Nicol <rick@zucchi.co.uk>
 * @package ZucchiDoctrine\Driver\Mysqli
 * @subpackage
 */
class MysqliConnection implements Connection
{
    /**
     * @var \mysqli
     */
    private $_conn;

    public function __construct(array $params, $username, $password, array $driverOptions = array())
    {
        $port = isset($params['port']) ? $params['port'] : ini_get('mysqli.default_port');
        $socket = isset($params['unix_socket']) ? $params['unix_socket'] : ini_get('mysqli.default_socket');
        $flags = isset($params['flags']) ? $params['flags'] : null;

        $this->_conn = mysqli_init();
        if ( ! $this->_conn->real_connect($params['host'], $username, $password, $params['dbname'], $port, $socket, $flags)) {
            throw new MysqliException($this->_conn->connect_error, $this->_conn->connect_errno);
        }

        if (isset($params['charset'])) {
            $this->_conn->set_charset($params['charset']);
        }
    }

    /**
     * Retrieve mysqli native resource handle.
     *
     * Could be used if part of your application is not using DBAL
     *
     * @return \mysqli
     */
    public function getWrappedResourceHandle()
    {
        return $this->_conn;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        return new MysqliStatement($this->_conn, $prepareString);
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type=\PDO::PARAM_STR)
    {
        return "'". $this->_conn->escape_string($input) ."'";
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        $this->_conn->query($statement);
        return $this->_conn->affected_rows;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        return $this->_conn->insert_id;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->_conn->query('START TRANSACTION');
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->_conn->commit();
    }

    /**
     * {@inheritdoc}non-PHPdoc)
     */
    public function rollBack()
    {
        return $this->_conn->rollback();
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->_conn->errno;
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return $this->_conn->error;
    }
}
