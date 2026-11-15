/**
 * Ziggy's route() throws when a route name is missing from the list the server sent.
 * That happens on a live server whose route cache is older than the frontend build
 * (deployed without `php artisan optimize:clear`): one missing name in a template
 * stops the whole page from rendering and the visitor sees a blank screen.
 *
 * The wrapped helper logs the missing name and returns '#' instead, so the page still
 * renders. route() with no arguments (route().has(), route().current()) is unchanged.
 */
export function makeSafeRoute(route) {
    if (typeof route !== 'function' || route.__safe) {
        return route;
    }

    const safe = function (...args) {
        if (args.length === 0 || args[0] === undefined) {
            return route(...args);
        }

        try {
            return route(...args);
        } catch (error) {
            console.error(`[route] "${args[0]}" is not in the route list; the server's route cache may be out of date.`, error);

            return '#';
        }
    };

    safe.__safe = true;

    return safe;
}

/** True when every named route exists in the list the server sent. */
export function routesExist(...names) {
    try {
        const router = window.route?.();

        return names.every((name) => router?.has(name));
    } catch {
        return false;
    }
}
