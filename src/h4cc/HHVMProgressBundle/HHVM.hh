<?hh // strict

namespace h4cc\HHVMProgressBundle;

class HHVM
{
    /** Not a Travis.yml build */
    const int STATUS_UNKNOWN = 0;
    /** HHVM is not in travis.yml */
    const int STATUS_NONE = 1;
    /** HHVM is a allowed failure build. */
    const int STATUS_ALLOWED_FAILURE = 2;
    /** HHVM is a full build. */
    const int STATUS_SUPPORTED = 3;

    /**
     * Returns a list of all possible hhvm status.
     *
     * @return array
     */
    public static function getAllHHVMStatus() : array<int>
    {
        return [
            self::STATUS_UNKNOWN,
            self::STATUS_NONE,
            self::STATUS_ALLOWED_FAILURE,
            self::STATUS_SUPPORTED
        ];
    }

    /**
     * Returns a readable string for a status.
     *
     * @param int $status
     * @return string
     */
    public static function getStringForStatus(int $status) : string
    {
        switch($status) {
            case static::STATUS_UNKNOWN:
                return 'unknown';
            case static::STATUS_NONE:
                return 'not_tested';
            case static::STATUS_ALLOWED_FAILURE:
                return 'partial';
            case static::STATUS_SUPPORTED:
                return 'tested';
        }
        return 'unknown';
    }
}
