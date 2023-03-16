<?php

namespace FSVendor\WPDesk\Nps;

/**
 * Provides user meta data for NPS.
 */
class UserMeta
{
    const DISMISS_COUNT = 'dismiss_count';
    const DISMISS_TIME = 'dismiss_time';
    const SENT = 'sent';
    /**
     * @var \WP_User
     */
    private $current_user;
    /**
     * @var string
     */
    private $user_meta_name;
    /**
     * @var array
     */
    private $meta_value;
    /**
     * UserMeta constructor.
     *
     * @param \WP_User $current_user
     * @param string $user_meta_name
     */
    public function __construct($current_user, $user_meta_name)
    {
        $this->user_meta_name = $user_meta_name;
        $this->current_user = $current_user;
        $this->meta_value = $this->prepare_user_meta(\get_user_meta($current_user->ID, $this->user_meta_name, \true));
    }
    /**
     * @return array
     */
    public function get_meta_value()
    {
        return $this->meta_value;
    }
    /**
     * @param string $field
     * @param string|int $default
     *
     * @return string|int
     */
    public function get_from_meta_value($field, $default)
    {
        return isset($this->meta_value[$field]) ? $this->meta_value[$field] : $default;
    }
    public function update_dismiss_meta()
    {
        $this->update_meta(self::DISMISS_COUNT, isset($this->meta_value[self::DISMISS_COUNT]) ? (int) $this->meta_value[self::DISMISS_COUNT] + 1 : 1);
        $this->update_meta(self::DISMISS_TIME, \current_time('timestamp'));
        $this->save();
    }
    public function update_sent_meta()
    {
        $this->update_meta(self::SENT, 'yes');
        $this->save();
    }
    /**
     * @param string $key .
     * @param mixed $value .
     *
     * @return void
     */
    public function update_meta(string $key, $value)
    {
        $this->meta_value[$key] = $value;
    }
    /**
     * @param string $key .
     *
     * @return void
     */
    public function delete_meta(string $key)
    {
        unset($this->meta_value[$key]);
    }
    /**
     * @return void
     */
    public function save()
    {
        \update_user_meta($this->current_user->ID, $this->user_meta_name, $this->meta_value);
    }
    private function prepare_user_meta($user_meta)
    {
        $user_meta = \is_array($user_meta) ? $user_meta : array();
        return $user_meta;
    }
}
