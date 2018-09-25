<?php
/**
 * Created by PhpStorm.
 * User: boellmann
 * Date: 25.09.18
 * Time: 10:15
 */

namespace Lexik\Bundle\MaintenanceBundle\Drivers;

use Symfony\Component\Translation\TranslatorInterface;

interface DriverInterface
{
    /**
     * Test if object exists
     *
     * @return boolean
     */
    public function isExists();

    /**
     * The feedback message
     *
     * @param boolean $resultTest The result of lock
     *
     * @return string
     */
    public function getMessageLock($resultTest);

    /**
     * The feedback message
     *
     * @param boolean $resultTest The result of unlock
     *
     * @return string
     */
    public function getMessageUnlock($resultTest);

    /**
     * The response of lock
     *
     * @return boolean
     */
    public function lock();

    /**
     * The response of unlock
     *
     * @return boolean
     */
    public function unlock();

    /**
     * the choice of the driver to less pass or not the user
     *
     * @return boolean
     */
    public function decide();

    /**
     * Options of driver
     *
     * @return array
     */
    public function getOptions();

    /**
     * Set translatorlator
     *
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator);

    /**
     * Get the remaining time to live of the maintenance lock, return null if no ttl is set or if not locked
     * @return \DateInterval|null
     */
    public function getRemainingTimeToLive(): ?\DateInterval;
}