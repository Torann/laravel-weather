<?php namespace Torann\LaravelWeather;

use Illuminate\View\Factory;
use Illuminate\Cache\CacheManager;

class Weather
{
    /**
     * Cache manager
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Factory view.
     *
     * @var \Illuminate\View\Factory
     */
    protected $view;

    /**
     * Weather config.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Cache\CacheManager $cache
     * @param \Illuminate\View\Factory       $view
     * @param array                          $config
     */
    public function __construct(CacheManager $cache, Factory $view, $config)
    {
        $this->cache  = $cache;
        $this->view   = $view;
        $this->config = $config;
    }

    /**
     * Render weather widget by location name.
     *
     * @param  string $name
     * @return string
     */
    public function renderByName($name = null)
    {
        // Remove commas
        $name = strtolower(str_replace(', ', ',', $name));

        return $this->generate(array(
            'query' => "q={$name}"
        ));
    }

    /**
     * Render weather widget by geo point.
     *
     * @param  float  $lat
     * @param  float  $lon
     * @return string
     */
    public function renderByPoint($lat, $lon)
    {
        return $this->generate(array(
            'query' => "lat={$lat}&lon={$lon}"
        ));
    }

    /**
     * Render weather widget.
     *
     * @param  array  $options
     * @return string
     */
    public function generate($options = array())
    {
        // Get options
        $options = array_merge($this->config['defaults'], $options);

        // Create cache key
        $cacheKey = 'Weather.'.md5(implode($options));

        // Check cache
        if ($this->config['cache'] && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Get current weather
        $current = $this->getWeather($options['query'], 0, $options['units'], 1);

        if($current['cod'] !== 200) {
            return 'Unable to load weather';
        }

        // Get forecast
        $forecast = $this->getWeather($options['query'], $options['days'], $options['units']);

        // Render view
        $html = $this->view->make("{$this->config['views']}.{$options['style']}", array(
            'current'  => $current,
            'forcast'  => $forecast,
            'units'    => $options['units'],
            'date'     => $options['date']
        ))->render();

        // Add to cache
        if ($this->config['cache']) {
            $this->cache->put($cacheKey, $html, $this->config['cache']);
        }

        return $html;
    }

    public function getIcon($code)
    {
        switch ($code) {
            case 200 : return '0'; break;
            case 201 : return '0'; break;
            case 202 : return '0'; break;
            case 210 : return '0'; break;
            case 211 : return '0'; break;
            case 212 : return '0'; break;
            case 221 : return '0'; break;
            case 230 : return '0'; break;
            case 231 : return '0'; break;
            case 232 : return '0'; break;
            case 300 : return 'R'; break;
            case 301 : return 'R'; break;
            case 302 : return 'R'; break;
            case 310 : return 'R'; break;
            case 311 : return 'R'; break;
            case 312 : return 'R'; break;
            case 321 : return 'R'; break;
            case 500 : return 'Q'; break;
            case 501 : return 'Q'; break;
            case 502 : return 'Q'; break;
            case 503 : return 'Q'; break;
            case 504 : return 'Q'; break;
            case 511 : return 'X'; break;
            case 520 : return 'R'; break;
            case 521 : return 'R'; break;
            case 522 : return 'R'; break;
            case 600 : return 'U'; break;
            case 601 : return 'W'; break;
            case 602 : return 'W'; break;
            case 611 : return 'W'; break;
            case 621 : return 'W'; break;
            case 701 : return 'M'; break;
            case 711 : return 'M'; break;
            case 721 : return 'M'; break;
            case 731 : return 'M'; break;
            case 741 : return 'M'; break;
            case 800 : return 'B'; break;
            case 801 : return 'H'; break;
            case 802 : return 'N'; break;
            case 803 : return 'Y'; break;
            case 804 : return 'Y'; break;
            case 900 : return 'F'; break;
            case 901 : return 'F'; break;
            case 902 : return 'F'; break;
            case 905 : return 'F'; break;
            case 906 : return 'G'; break;
        }
    }

    public function getWindDirection($deg)
    {
        if ($deg >= 0 && $deg < 22.5) return 'N';
        elseif ($deg >= 22.5 && $deg < 45) return 'NNE';
        elseif ($deg >= 45 && $deg < 67.5) return 'NE';
        elseif ($deg >= 67.5 && $deg < 90) return 'ENE';
        elseif ($deg >= 90 && $deg < 122.5) return 'E';
        elseif ($deg >= 112.5 && $deg < 135) return 'ESE';
        elseif ($deg >= 135 && $deg < 157.5) return 'SE';
        elseif ($deg >= 157.5 && $deg < 180) return 'SSE';
        elseif ($deg >= 180 && $deg < 202.5) return 'S';
        elseif ($deg >= 202.5 && $deg < 225) return 'SSW';
        elseif ($deg >= 225 && $deg < 247.5) return 'SW';
        elseif ($deg >= 247.5 && $deg < 270) return 'WSW';
        elseif ($deg >= 270 && $deg < 292.5) return 'W';
        elseif ($deg >= 292.5 && $deg < 315) return 'WNW';
        elseif ($deg >= 315 && $deg < 337.5) return 'NW';
        elseif ($deg >= 337.5 && $deg < 360) return 'NNW';
    }

    private function getWeather($query, $days = 1, $units = 'internal', $type = 0, $lang = 'en')
    {
        $forecast = ($type == 0) ? 'forecast/daily?' : 'weather?';
        return $this->request("http://api.openweathermap.org/data/2.5/{$forecast}{$query}&cnt={$days}&units={$units}&mode=json&lang={$lang}");
    }

    private function request($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ch, CURLOPT_MAXCONNECTS, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode( $response, true );
    }
}
