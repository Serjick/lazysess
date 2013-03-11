<?php

namespace Lazysess;

class Session extends Arr
{
    const LOGGING = 0;
    private $log = array();
    private $snapshot;

    public function __construct()
    {
        if (session_id()) {
            $this->save();
        }
    }

    private function start()
    {
        session_start();
    }

    private function save($keep_alive = true)
    {
        session_write_close();

        if ($keep_alive) {
            $_SESSION = $this;
        }
    }

    public function offsetExists($offset)
    {
        $this->addLog(__METHOD__ . ':' . $offset);
        return parent::offsetExists($offset);
    }

    public function &offsetGet($offset)
    {
        $this->addLog(__METHOD__ . ':' . $offset);
        $result = & parent::offsetGet($offset);
        return $result;
    }

    public function offsetSet($offset, $value)
    {
        $this->addLog(__METHOD__ . ':' . $offset);
        $this->start();
        $_SESSION[$offset] = $value;

        if ($this->isDataLoad()) {
            parent::offsetSet($offset, $value);
            $this->snapshot[$offset] = $value;
        }

        $this->save();
    }

    public function offsetUnset($offset)
    {
        $this->addLog(__METHOD__ . ':' . $offset);
        $this->start();
        unset($_SESSION[$offset]);

        if ($this->isDataLoad()) {
            parent::offsetUnset($offset);
            unset($this->snapshot[$offset]);
        }

        $this->save();
    }

    protected function &getData()
    {
        if (!$this->isDataLoad()) {
            $this->start();
            $this->snapshot = $_SESSION;
            $this->setData($this->snapshot);
            $this->save();
        }

        $result = & parent::getData();
        return $result;
    }

    private function isDataLoad()
    {
        return parent::getData() !== null;
    }

    private function isDataModifiedByReference()
    {
        return $this->isDataLoad() && $this->getData() != $this->snapshot;
    }

    private function addLog($message, $to_log_or_not_to_log = self::LOGGING)
    {
        if ($to_log_or_not_to_log) {
            $this->log[] = $message;
        }
    }

    public function close()
    {
        if ($this->isDataModifiedByReference()) {
            $this->start();
            $merger = new Merge($_SESSION, $this->getData(), $this->snapshot);
            $_SESSION = $merger->getMerge();

            foreach ($merger->getCollisions() as $collision) {
                trigger_error('session_race_condition: ' . $collision, E_USER_NOTICE);
            }

            $this->save(false);
        }
    }

}
