<?php

use App\Dorcas\Hub\Utilities\Gravatar;
use App\Dorcas\Hub\Utilities\UiResponse\MaterialUiResponse;
use App\Dorcas\Support\Tabler\TablerNotification;
use App\Dorcas\Support\UiResponse\ToastNotification;
use Illuminate\Validation\ValidationException;

/**
 * Generates an asset URL for static content, taking the CDN into consideration
 *
 * @param string $path
 * @param bool   $secure
 *
 * @return string
 */
function cdn(string $path, bool $secure = true)
{
    $base = config('app.url_static', config('app.url'));
    # we get the base URL first
    $uri = new \GuzzleHttp\Psr7\Uri($base);
    # create the URI
    if (!empty($path) && !is_string($path)) {
        throw new InvalidArgumentException('path should either be a string');
    }
    if (!empty($path)) {
        $uri = $uri->withPath(starts_with($path, '/') ? $path : '/'.$path);
    }
    if ($secure) {
        $uri = $uri->getScheme() === 'http' ? $uri : $uri->withScheme('https');
    } else {
        $uri = $uri->withScheme('http');
    }
    return (string) $uri;
}

/**
 * @param stdClass $partner
 * @param string   $path
 * @param array    $query
 *
 * @return null|string
 */
function generate_partner_url(stdClass $partner, string $path = '', array $query = []): ?string
{
    if (empty($partner->domain_issuances) || empty($partner->domain_issuances['data'])) {
        return null;
    }
    $issuance = (object) $partner->domain_issuances['data'][0];
    # we get the first entry in the issuances array
    $domain = 'dorcas.io';
    if (!empty($issuance->domain) && !empty($issuance->domain['data'])) {
        $domain = $issuance->domain['data']['domain'];
    }
    $scheme = app()->environment() === 'production' ? 'https' : 'http';
    $uri = new \GuzzleHttp\Psr7\Uri($scheme . '://' . $issuance->prefix . '.' . $domain);
    if (!empty($path)) {
        $uri = $uri->withPath($path[0] === '/' ? $path : '/' . $path);
    }
    if (!empty($query)) {
        $query = is_array($query) ? http_build_query($query) : $query;
        $uri = $uri->withQuery($query);
    }
    return (string) $uri;
}

/**
 * A simpler way to generate the gravatar
 *
 * @param string $email
 * @param bool   $secure
 * @param int    $width
 * @param string $default
 * @param string $rating
 *
 * @return string
 */
function gravatar(
    string $email,
    bool $secure = true,
    int $width = 400,
    string $default = Gravatar::DEFAULT_IMG_RETRO,
    string $rating = Gravatar::RATED_G
): string {
    return Gravatar::getGravatar($email, $secure, $width, $default, $rating);
}

/**
 * Creates and returns an instance of the HTML alert view for the material ui theme.
 *
 * @param array $messages
 *
 * @return MaterialUiResponse
 */
function material_ui_html_response(array $messages = []): \App\Dorcas\Hub\Utilities\UiResponse\UiResponseInterface
{
    return new MaterialUiResponse($messages);
}

/**
 * @param array $messages
 *
 * @return \App\Dorcas\Hub\Utilities\UiResponse\UiResponseInterface
 */
function tabler_ui_html_response(array $messages = []): \App\Dorcas\Hub\Utilities\UiResponse\UiResponseInterface
{
    return new \App\Dorcas\Hub\Utilities\UiResponse\TablerUiResponse($messages);
}

/**
 * Creates the tabler notification from the supplied data.
 *
 * @param string      $actor
 * @param string      $activity
 * @param             $action
 * @param             $timestamp
 * @param string|null $photo
 *
 * @return TablerNotification
 */
function tabler_notification(string $actor, string $activity, $action, $timestamp, string $photo = null): TablerNotification
{
    return new TablerNotification($actor, $activity, $action, $timestamp, $photo);
}

/**
 * Converts a validation exception to the appropriate human-understandable text.
 *
 * @param ValidationException $exception
 *
 * @return array
 */
function validation_errors_to_messages(ValidationException $exception)
{
    $messages = [];
    $errors = [];
    foreach ($exception->validator->failed() as $field => $failures) {
        foreach ($failures as $rule => $data) {
            $errors[$field][$rule] = is_array($data) ? implode(', ', $data) : $data;
        }
    }
    foreach ($exception->errors() as $field => $failures) {
        $messages[$field] = $failures;
    }
    return $messages;
}

/**
 * Calculates what the page number should be based on the supplied offset, and limit values.
 *
 * @param int $offset
 * @param int $limit
 *
 * @return int
 */
function get_page_number(int $offset, int $limit): int
{
    return (int) (($offset + $limit) / $limit);
}

/**
 * Suggests an icon name to use for a custom contact field based on its name.
 *
 * @param string      $name
 * @param string|null $default
 *
 * @return string
 */
function suggest_contact_field_icon_name(string $name, string $default = null): string
{
    $name = strtolower($name);
    # adjust the case
    if (str_contains($name, ['email', 'mail'])) {
        return 'mail_outline';
    } elseif (str_contains($name, ['date', 'birth'])) {
        return 'date_range';
    } elseif (str_contains($name, ['mobile', 'phone'])) {
        return 'phonelink_ring';
    } elseif (str_contains($name, ['address'])) {
        return 'location_on';
    } elseif (str_contains($name, ['pay', 'payment', 'card'])) {
        return 'payment';
    } elseif (str_contains($name, ['company', 'business'])) {
        return 'domain';
    } elseif (str_contains($name, ['twitter', 'facebook', 'google', 'youtube', 'pinterest', 'instagram'])) {
        return 'public';
    }
    return $default ?: 'assignment_ind';
}

/**
 * Suggests an icon name to use for a custom contact field based on its name.
 *
 * @param string      $name
 * @param string|null $default
 *
 * @return string
 */
function suggest_contact_field_icon_name_tabler(string $name, string $default = null): string
{
    $name = strtolower($name);
    # adjust the case
    if (str_contains($name, ['email', 'mail'])) {
        return 'fa-cogs';
    } elseif (str_contains($name, ['date', 'birth'])) {
        return 'fa-cogs';
    } elseif (str_contains($name, ['mobile', 'phone'])) {
        return 'fa-cogs';
    } elseif (str_contains($name, ['address'])) {
        return 'fa-institution';
    } elseif (str_contains($name, ['pay', 'payment', 'card'])) {
        return 'fa-cogs';
    } elseif (str_contains($name, ['company', 'business'])) {
        return 'fa-cogs';
    } elseif (str_contains($name, ['twitter', 'facebook', 'google', 'youtube', 'pinterest', 'instagram'])) {
        return 'fa-cogs';
    }
    return $default ?: 'fa-cogs';
}
/**
 * Returns the base URL for custom subdomains.
 *
 * @return string
 */
function get_dorcas_domain(): string
{
    return 'dorcas.' . (app()->environment() === 'production' ? 'io' : 'local');
}

/**
 * Tries to get the Dorcas.ng subdomain for the currently authenticated account.
 *
 * @param \Hostville\Dorcas\Sdk|null $sdk
 *
 * @return null|string
 */
function get_dorcas_subdomain(\Hostville\Dorcas\Sdk $sdk = null)
{
    $subdomains = (new \App\Http\Controllers\Controller())->getSubDomains($sdk);
    # get the subdomains for the authenticated account
    if (empty($subdomains) || $subdomains->count() === 0) {
        # none found
        return null;
    }
    $subdomain = $subdomains->first();
    $scheme = app()->environment() === 'production' ? 'https' : 'http';
    return $scheme . '://' . $subdomain->prefix . '.' . $subdomain->domain['data']['domain'];
}

/**
 * Creates a new Toast instance.
 *
 * @param string $message
 * @param int    $durationSecs
 * @param array  $config
 *
 * @return ToastNotification
 */
function toast(string $message, int $durationSecs = 10, array $config = []): ToastNotification
{
    return new ToastNotification($message, $durationSecs * 1000, $config);
}


function safe_href_route($route) {
    if (\Route::has($route)) {
        return "1";
    } else {
        return "0";
    }
}
