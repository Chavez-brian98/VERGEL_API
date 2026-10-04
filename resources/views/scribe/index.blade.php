<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>VERGEL API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.11.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.11.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-clientes" class="tocify-header">
                <li class="tocify-item level-1" data-unique="clientes">
                    <a href="#clientes">Clientes</a>
                </li>
                                    <ul id="tocify-subheader-clientes" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="clientes-consultas">
                                <a href="#clientes-consultas">Consultas</a>
                            </li>
                                                            <ul id="tocify-subheader-clientes-consultas" class="tocify-subheader">
                                                                            <li class="tocify-item level-3" data-unique="clientes-GETapi-v1-customers">
                                            <a href="#clientes-GETapi-v1-customers">Listar y filtrar clientes.</a>
                                        </li>
                                                                            <li class="tocify-item level-3" data-unique="clientes-GETapi-v1-customers--id-">
                                            <a href="#clientes-GETapi-v1-customers--id-">Obtener detalle completo de un cliente.</a>
                                        </li>
                                                                            <li class="tocify-item level-3" data-unique="clientes-GETapi-v1-customers--customer--plan-history">
                                            <a href="#clientes-GETapi-v1-customers--customer--plan-history">Obtener el historial de planes de un cliente.</a>
                                        </li>
                                                                    </ul>
                                                                                <li class="tocify-item level-2" data-unique="clientes-escritura">
                                <a href="#clientes-escritura">Escritura</a>
                            </li>
                                                            <ul id="tocify-subheader-clientes-escritura" class="tocify-subheader">
                                                                            <li class="tocify-item level-3" data-unique="clientes-POSTapi-v1-customers">
                                            <a href="#clientes-POSTapi-v1-customers">Registrar un nuevo cliente.</a>
                                        </li>
                                                                            <li class="tocify-item level-3" data-unique="clientes-PUTapi-v1-customers--id-">
                                            <a href="#clientes-PUTapi-v1-customers--id-">Actualizar información del cliente.</a>
                                        </li>
                                                                            <li class="tocify-item level-3" data-unique="clientes-DELETEapi-v1-customers--id-">
                                            <a href="#clientes-DELETEapi-v1-customers--id-">Eliminar un cliente (soft delete).</a>
                                        </li>
                                                                            <li class="tocify-item level-3" data-unique="clientes-PATCHapi-v1-customers--customer--status">
                                            <a href="#clientes-PATCHapi-v1-customers--customer--status">Inactivar / cambiar estado de un cliente.</a>
                                        </li>
                                                                            <li class="tocify-item level-3" data-unique="clientes-POSTapi-v1-customers--customer--plan">
                                            <a href="#clientes-POSTapi-v1-customers--customer--plan">Cambiar el plan de suscripción de un cliente.</a>
                                        </li>
                                                                    </ul>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: October 3, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>API REST de VERGEL para la gestión de clientes, suscripciones, servicios, cotizaciones, billeteras y puntos de lealtad. Operaciones orientadas a El Salvador (DTE, DUI, NRC, IVA).</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>
<p>Esta documentación describe los endpoints disponibles en la API de <strong>VERGEL</strong>.</p>
<aside>Para consumir los endpoints protegidos necesitas un token de acceso. Envíalo como cabecera `Authorization: Bearer {token}`.</aside>
<p>Todos los endpoints requieren autenticación salvo que se indique lo contrario.</p>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer {YOUR_AUTH_TOKEN}"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>Los tokens se generan mediante Laravel Sanctum. Para obtener tu token, inicia sesión en la aplicación y emite un nuevo token de acceso.</p>

        <h1 id="clientes">Clientes</h1>

    

                        <h2 id="clientes-consultas">Consultas</h2>
                                                    <h2 id="clientes-GETapi-v1-customers">Listar y filtrar clientes.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Obtiene una lista paginada de clientes con opciones de búsqueda y filtros.</p>

<span id="example-requests-GETapi-v1-customers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/v1/customers?search=architecto&amp;status=active&amp;phone=architecto&amp;desde=architecto&amp;hasta=architecto&amp;limit=15" \
    --header "Authorization: Bearer {YOUR_AUTH_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/customers"
);

const params = {
    "search": "architecto",
    "status": "active",
    "phone": "architecto",
    "desde": "architecto",
    "hasta": "architecto",
    "limit": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-customers">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;legalName&quot;: &quot;Cliente Ejemplo S.A. de C.V.&quot;,
            &quot;tradeName&quot;: &quot;Cliente Ejemplo&quot;,
            &quot;taxId&quot;: &quot;0614-260290-101-1&quot;,
            &quot;nrc&quot;: &quot;123456-7&quot;,
            &quot;phone&quot;: &quot;7000-0000&quot;,
            &quot;status&quot;: &quot;active&quot;,
            &quot;currentPlanName&quot;: &quot;Plan B&aacute;sico&quot;,
            &quot;cashBalance&quot;: 250.5,
            &quot;loyaltyPoints&quot;: 120,
            &quot;createdAt&quot;: &quot;2026-09-23T06:00:00+00:00&quot;
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;http://localhost/api/v1/customers?page=1&quot;,
        &quot;last&quot;: &quot;http://localhost/api/v1/customers?page=1&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: null
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 1,
        &quot;path&quot;: &quot;http://localhost/api/v1/customers&quot;,
        &quot;per_page&quot;: 15,
        &quot;to&quot;: 1,
        &quot;total&quot;: 1
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-customers" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-customers"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-customers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-customers" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-customers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-customers" data-method="GET"
      data-path="api/v1/customers"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-customers', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-customers"
                    onclick="tryItOut('GETapi-v1-customers');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-customers"
                    onclick="cancelTryOut('GETapi-v1-customers');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-customers"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/customers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-customers"
               value="Bearer {YOUR_AUTH_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_AUTH_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-customers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-customers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-customers"
               value="architecto"
               data-component="query">
    <br>
<p>Busca por nombre legal, nombre comercial, correo, teléfono, NIT o NRC. Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-customers"
               value="active"
               data-component="query">
    <br>
<p>Filtrar por estado. Example: <code>active</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="GETapi-v1-customers"
               value="architecto"
               data-component="query">
    <br>
<p>Filtrar por teléfono (coincidencia parcial). Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>desde</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="desde"                data-endpoint="GETapi-v1-customers"
               value="architecto"
               data-component="query">
    <br>
<p>date Filtrar por fecha de creación desde (YYYY-MM-DD). Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>hasta</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="hasta"                data-endpoint="GETapi-v1-customers"
               value="architecto"
               data-component="query">
    <br>
<p>date Filtrar por fecha de creación hasta (YYYY-MM-DD). Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="limit"                data-endpoint="GETapi-v1-customers"
               value="15"
               data-component="query">
    <br>
<p>Cantidad de resultados por página. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="clientes-GETapi-v1-customers--id-">Obtener detalle completo de un cliente.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Incluye el plan actual, la wallet y los puntos de lealtad del cliente.</p>

<span id="example-requests-GETapi-v1-customers--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/v1/customers/architecto" \
    --header "Authorization: Bearer {YOUR_AUTH_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/customers/architecto"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-customers--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;legalName&quot;: &quot;Cliente Ejemplo S.A. de C.V.&quot;,
        &quot;tradeName&quot;: &quot;Cliente Ejemplo&quot;,
        &quot;taxId&quot;: &quot;0614-260290-101-1&quot;,
        &quot;nrc&quot;: &quot;123456-7&quot;,
        &quot;economicActivity&quot;: &quot;Venta de servicios&quot;,
        &quot;address&quot;: &quot;Colonia Centro, San Salvador&quot;,
        &quot;municipality&quot;: &quot;San Salvador&quot;,
        &quot;department&quot;: &quot;San Salvador&quot;,
        &quot;phone&quot;: &quot;7000-0000&quot;,
        &quot;email&quot;: &quot;cliente@example.com&quot;,
        &quot;status&quot;: &quot;active&quot;,
        &quot;visitFrequency&quot;: {
            &quot;value&quot;: 30,
            &quot;unit&quot;: &quot;days&quot;
        },
        &quot;currentPlan&quot;: {
            &quot;id&quot;: 1,
            &quot;planCode&quot;: &quot;BASICO&quot;,
            &quot;planName&quot;: &quot;Plan B&aacute;sico&quot;
        },
        &quot;wallet&quot;: {
            &quot;cashBalance&quot;: 250.5,
            &quot;reservedSubscriptionBalance&quot;: 0
        },
        &quot;loyaltyPoints&quot;: {
            &quot;pointsBalance&quot;: 120
        },
        &quot;createdAt&quot;: &quot;2026-09-23T06:00:00+00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;No query results for model [App\\Models\\Customer]&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-customers--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-customers--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-customers--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-customers--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-customers--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-customers--id-" data-method="GET"
      data-path="api/v1/customers/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-customers--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-customers--id-"
                    onclick="tryItOut('GETapi-v1-customers--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-customers--id-"
                    onclick="cancelTryOut('GETapi-v1-customers--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-customers--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/customers/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-customers--id-"
               value="Bearer {YOUR_AUTH_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_AUTH_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-customers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-customers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-v1-customers--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the customer. Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>customer</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="customer"                data-endpoint="GETapi-v1-customers--id-"
               value="1"
               data-component="url">
    <br>
<p>ID del cliente. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="clientes-GETapi-v1-customers--customer--plan-history">Obtener el historial de planes de un cliente.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Devuelve los planes que ha tenido el cliente, ordenados por fecha de inicio (descendente).</p>

<span id="example-requests-GETapi-v1-customers--customer--plan-history">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/v1/customers/1/plan-history" \
    --header "Authorization: Bearer {YOUR_AUTH_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/customers/1/plan-history"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-customers--customer--plan-history">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 2,
            &quot;customer_id&quot;: 1,
            &quot;plan_id&quot;: 2,
            &quot;start_date&quot;: &quot;2026-10-01&quot;,
            &quot;end_date&quot;: null,
            &quot;plan&quot;: {
                &quot;id&quot;: 2,
                &quot;plan_code&quot;: &quot;PREMIUM&quot;,
                &quot;plan_name&quot;: &quot;Plan Premium&quot;
            }
        }
    ]
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;No query results for model [App\\Models\\Customer]&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-customers--customer--plan-history" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-customers--customer--plan-history"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-customers--customer--plan-history"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-customers--customer--plan-history" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-customers--customer--plan-history">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-customers--customer--plan-history" data-method="GET"
      data-path="api/v1/customers/{customer}/plan-history"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-customers--customer--plan-history', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-customers--customer--plan-history"
                    onclick="tryItOut('GETapi-v1-customers--customer--plan-history');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-customers--customer--plan-history"
                    onclick="cancelTryOut('GETapi-v1-customers--customer--plan-history');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-customers--customer--plan-history"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/customers/{customer}/plan-history</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-customers--customer--plan-history"
               value="Bearer {YOUR_AUTH_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_AUTH_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-customers--customer--plan-history"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-customers--customer--plan-history"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>customer</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="customer"                data-endpoint="GETapi-v1-customers--customer--plan-history"
               value="1"
               data-component="url">
    <br>
<p>ID del cliente. Example: <code>1</code></p>
            </div>
                    </form>

                                <h2 id="clientes-escritura">Escritura</h2>
                                                    <h2 id="clientes-POSTapi-v1-customers">Registrar un nuevo cliente.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Crea un cliente y, como parte del proceso, inicializa su wallet y sus puntos de lealtad.</p>

<span id="example-requests-POSTapi-v1-customers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/customers" \
    --header "Authorization: Bearer {YOUR_AUTH_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"legal_name\": \"Cliente Ejemplo S.A. de C.V.\",
    \"trade_name\": \"Cliente Ejemplo\",
    \"tax_id\": \"0614-260290-101-1\",
    \"nrc\": \"123456-7\",
    \"economic_activity\": \"Venta de servicios\",
    \"address\": \"Colonia Centro, San Salvador\",
    \"department\": \"San Salvador\",
    \"municipality\": \"San Salvador\",
    \"phone\": \"7000-0000\",
    \"email\": \"cliente@example.com\",
    \"visit_frequency_value\": 30,
    \"visit_frequency_unit\": \"days\",
    \"current_plan_id\": 1
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/customers"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "legal_name": "Cliente Ejemplo S.A. de C.V.",
    "trade_name": "Cliente Ejemplo",
    "tax_id": "0614-260290-101-1",
    "nrc": "123456-7",
    "economic_activity": "Venta de servicios",
    "address": "Colonia Centro, San Salvador",
    "department": "San Salvador",
    "municipality": "San Salvador",
    "phone": "7000-0000",
    "email": "cliente@example.com",
    "visit_frequency_value": 30,
    "visit_frequency_unit": "days",
    "current_plan_id": 1
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-customers">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;legalName&quot;: &quot;Cliente Ejemplo S.A. de C.V.&quot;,
        &quot;tradeName&quot;: &quot;Cliente Ejemplo&quot;,
        &quot;taxId&quot;: &quot;0614-260290-101-1&quot;,
        &quot;nrc&quot;: &quot;123456-7&quot;,
        &quot;economicActivity&quot;: &quot;Venta de servicios&quot;,
        &quot;address&quot;: &quot;Colonia Centro, San Salvador&quot;,
        &quot;municipality&quot;: &quot;San Salvador&quot;,
        &quot;department&quot;: &quot;San Salvador&quot;,
        &quot;phone&quot;: &quot;7000-0000&quot;,
        &quot;email&quot;: &quot;cliente@example.com&quot;,
        &quot;status&quot;: &quot;active&quot;,
        &quot;visitFrequency&quot;: null,
        &quot;currentPlan&quot;: null,
        &quot;wallet&quot;: {
            &quot;cashBalance&quot;: 0,
            &quot;reservedSubscriptionBalance&quot;: 0
        },
        &quot;loyaltyPoints&quot;: null,
        &quot;createdAt&quot;: &quot;2026-09-23T06:00:00+00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;The legal name field is required.&quot;,
    &quot;errors&quot;: {
        &quot;legal_name&quot;: [
            &quot;The legal name field is required.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-customers" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-customers"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-customers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-customers" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-customers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-customers" data-method="POST"
      data-path="api/v1/customers"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-customers', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-customers"
                    onclick="tryItOut('POSTapi-v1-customers');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-customers"
                    onclick="cancelTryOut('POSTapi-v1-customers');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-customers"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/customers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-customers"
               value="Bearer {YOUR_AUTH_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_AUTH_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-customers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-customers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>legal_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="legal_name"                data-endpoint="POSTapi-v1-customers"
               value="Cliente Ejemplo S.A. de C.V."
               data-component="body">
    <br>
<p>Nombre legal del cliente. Example: <code>Cliente Ejemplo S.A. de C.V.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>trade_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="trade_name"                data-endpoint="POSTapi-v1-customers"
               value="Cliente Ejemplo"
               data-component="body">
    <br>
<p>Nombre comercial. Example: <code>Cliente Ejemplo</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>tax_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tax_id"                data-endpoint="POSTapi-v1-customers"
               value="0614-260290-101-1"
               data-component="body">
    <br>
<p>NIT del cliente (único). Example: <code>0614-260290-101-1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>nrc</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="nrc"                data-endpoint="POSTapi-v1-customers"
               value="123456-7"
               data-component="body">
    <br>
<p>NRC del cliente (único). Example: <code>123456-7</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>economic_activity</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="economic_activity"                data-endpoint="POSTapi-v1-customers"
               value="Venta de servicios"
               data-component="body">
    <br>
<p>Actividad económica. Example: <code>Venta de servicios</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>address</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address"                data-endpoint="POSTapi-v1-customers"
               value="Colonia Centro, San Salvador"
               data-component="body">
    <br>
<p>Dirección. Example: <code>Colonia Centro, San Salvador</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>department</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="department"                data-endpoint="POSTapi-v1-customers"
               value="San Salvador"
               data-component="body">
    <br>
<p>Departamento. Example: <code>San Salvador</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>municipality</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="municipality"                data-endpoint="POSTapi-v1-customers"
               value="San Salvador"
               data-component="body">
    <br>
<p>Municipio. Example: <code>San Salvador</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-customers"
               value="7000-0000"
               data-component="body">
    <br>
<p>Teléfono (único). Example: <code>7000-0000</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-customers"
               value="cliente@example.com"
               data-component="body">
    <br>
<p>Correo electrónico. Example: <code>cliente@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>visit_frequency_value</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="visit_frequency_value"                data-endpoint="POSTapi-v1-customers"
               value="30"
               data-component="body">
    <br>
<p>Frecuencia de visita (valor numérico). Example: <code>30</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>visit_frequency_unit</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="visit_frequency_unit"                data-endpoint="POSTapi-v1-customers"
               value="days"
               data-component="body">
    <br>
<p>Unidad de frecuencia de visita. Example: <code>days</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>current_plan_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="current_plan_id"                data-endpoint="POSTapi-v1-customers"
               value="1"
               data-component="body">
    <br>
<p>ID del plan de suscripción inicial. Example: <code>1</code></p>
        </div>
        </form>

                    <h2 id="clientes-PUTapi-v1-customers--id-">Actualizar información del cliente.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Actualiza los datos editables de un cliente existente.</p>

<span id="example-requests-PUTapi-v1-customers--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost/api/v1/customers/architecto" \
    --header "Authorization: Bearer {YOUR_AUTH_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"legal_name\": \"Cliente Actualizado S.A. de C.V.\",
    \"trade_name\": \"Cliente Actualizado\",
    \"tax_id\": \"0614-260290-101-1\",
    \"nrc\": \"123456-7\",
    \"economic_activity\": \"Venta de servicios\",
    \"address\": \"Colonia Centro, San Salvador\",
    \"department\": \"San Salvador\",
    \"municipality\": \"San Salvador\",
    \"phone\": \"7000-0000\",
    \"email\": \"cliente@example.com\",
    \"visit_frequency_value\": 30,
    \"visit_frequency_unit\": \"days\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/customers/architecto"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "legal_name": "Cliente Actualizado S.A. de C.V.",
    "trade_name": "Cliente Actualizado",
    "tax_id": "0614-260290-101-1",
    "nrc": "123456-7",
    "economic_activity": "Venta de servicios",
    "address": "Colonia Centro, San Salvador",
    "department": "San Salvador",
    "municipality": "San Salvador",
    "phone": "7000-0000",
    "email": "cliente@example.com",
    "visit_frequency_value": 30,
    "visit_frequency_unit": "days"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-customers--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;legalName&quot;: &quot;Cliente Actualizado S.A. de C.V.&quot;,
        &quot;status&quot;: &quot;active&quot;,
        &quot;createdAt&quot;: &quot;2026-09-23T06:00:00+00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;The phone has already been taken.&quot;,
    &quot;errors&quot;: {
        &quot;phone&quot;: [
            &quot;The phone has already been taken.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-customers--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-customers--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-customers--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-customers--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-customers--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-customers--id-" data-method="PUT"
      data-path="api/v1/customers/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-customers--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-customers--id-"
                    onclick="tryItOut('PUTapi-v1-customers--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-customers--id-"
                    onclick="cancelTryOut('PUTapi-v1-customers--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-customers--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/customers/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/customers/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-v1-customers--id-"
               value="Bearer {YOUR_AUTH_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_AUTH_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-customers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-customers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-v1-customers--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the customer. Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>customer</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="customer"                data-endpoint="PUTapi-v1-customers--id-"
               value="1"
               data-component="url">
    <br>
<p>ID del cliente. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>legal_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="legal_name"                data-endpoint="PUTapi-v1-customers--id-"
               value="Cliente Actualizado S.A. de C.V."
               data-component="body">
    <br>
<p>Nombre legal del cliente. Example: <code>Cliente Actualizado S.A. de C.V.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>trade_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="trade_name"                data-endpoint="PUTapi-v1-customers--id-"
               value="Cliente Actualizado"
               data-component="body">
    <br>
<p>Nombre comercial. Example: <code>Cliente Actualizado</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>tax_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tax_id"                data-endpoint="PUTapi-v1-customers--id-"
               value="0614-260290-101-1"
               data-component="body">
    <br>
<p>NIT del cliente (único). Example: <code>0614-260290-101-1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>nrc</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="nrc"                data-endpoint="PUTapi-v1-customers--id-"
               value="123456-7"
               data-component="body">
    <br>
<p>NRC del cliente (único). Example: <code>123456-7</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>economic_activity</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="economic_activity"                data-endpoint="PUTapi-v1-customers--id-"
               value="Venta de servicios"
               data-component="body">
    <br>
<p>Actividad económica. Example: <code>Venta de servicios</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>address</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address"                data-endpoint="PUTapi-v1-customers--id-"
               value="Colonia Centro, San Salvador"
               data-component="body">
    <br>
<p>Dirección. Example: <code>Colonia Centro, San Salvador</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>department</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="department"                data-endpoint="PUTapi-v1-customers--id-"
               value="San Salvador"
               data-component="body">
    <br>
<p>Departamento. Example: <code>San Salvador</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>municipality</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="municipality"                data-endpoint="PUTapi-v1-customers--id-"
               value="San Salvador"
               data-component="body">
    <br>
<p>Municipio. Example: <code>San Salvador</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PUTapi-v1-customers--id-"
               value="7000-0000"
               data-component="body">
    <br>
<p>Teléfono (único). Example: <code>7000-0000</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="PUTapi-v1-customers--id-"
               value="cliente@example.com"
               data-component="body">
    <br>
<p>Correo electrónico. Example: <code>cliente@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>visit_frequency_value</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="visit_frequency_value"                data-endpoint="PUTapi-v1-customers--id-"
               value="30"
               data-component="body">
    <br>
<p>Frecuencia de visita (valor numérico). Example: <code>30</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>visit_frequency_unit</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="visit_frequency_unit"                data-endpoint="PUTapi-v1-customers--id-"
               value="days"
               data-component="body">
    <br>
<p>Unidad de frecuencia de visita. Example: <code>days</code></p>
        </div>
        </form>

                    <h2 id="clientes-DELETEapi-v1-customers--id-">Eliminar un cliente (soft delete).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Marca al cliente como eliminado. No se elimina físicamente de la base de datos.</p>

<span id="example-requests-DELETEapi-v1-customers--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost/api/v1/customers/architecto" \
    --header "Authorization: Bearer {YOUR_AUTH_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/customers/architecto"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-customers--id-">
            <blockquote>
            <p>Example response (204):</p>
        </blockquote>
                <pre>
<code>Empty response</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1-customers--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-customers--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-customers--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-customers--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-customers--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-customers--id-" data-method="DELETE"
      data-path="api/v1/customers/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-customers--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-customers--id-"
                    onclick="tryItOut('DELETEapi-v1-customers--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-customers--id-"
                    onclick="cancelTryOut('DELETEapi-v1-customers--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-customers--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/customers/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-customers--id-"
               value="Bearer {YOUR_AUTH_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_AUTH_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-customers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-customers--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-v1-customers--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the customer. Example: <code>architecto</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>customer</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="customer"                data-endpoint="DELETEapi-v1-customers--id-"
               value="1"
               data-component="url">
    <br>
<p>ID del cliente. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="clientes-PATCHapi-v1-customers--customer--status">Inactivar / cambiar estado de un cliente.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Actualiza el estado de un cliente (activo o inactivo) mediante PATCH.</p>

<span id="example-requests-PATCHapi-v1-customers--customer--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost/api/v1/customers/1/status" \
    --header "Authorization: Bearer {YOUR_AUTH_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"inactive\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/customers/1/status"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "inactive"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-customers--customer--status">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;legalName&quot;: &quot;Cliente Ejemplo S.A. de C.V.&quot;,
        &quot;status&quot;: &quot;inactive&quot;,
        &quot;createdAt&quot;: &quot;2026-09-23T06:00:00+00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;The selected status is invalid.&quot;,
    &quot;errors&quot;: {
        &quot;status&quot;: [
            &quot;The selected status is invalid.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-PATCHapi-v1-customers--customer--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-customers--customer--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-customers--customer--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-customers--customer--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-customers--customer--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-customers--customer--status" data-method="PATCH"
      data-path="api/v1/customers/{customer}/status"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-customers--customer--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-customers--customer--status"
                    onclick="tryItOut('PATCHapi-v1-customers--customer--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-customers--customer--status"
                    onclick="cancelTryOut('PATCHapi-v1-customers--customer--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-customers--customer--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/customers/{customer}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-customers--customer--status"
               value="Bearer {YOUR_AUTH_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_AUTH_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-customers--customer--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-customers--customer--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>customer</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="customer"                data-endpoint="PATCHapi-v1-customers--customer--status"
               value="1"
               data-component="url">
    <br>
<p>ID del cliente. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-customers--customer--status"
               value="inactive"
               data-component="body">
    <br>
<p>Nuevo estado del cliente. Example: <code>inactive</code></p>
        </div>
        </form>

                    <h2 id="clientes-POSTapi-v1-customers--customer--plan">Cambiar el plan de suscripción de un cliente.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Asigna un nuevo plan al cliente, cerrando el historial del plan anterior.</p>

<span id="example-requests-POSTapi-v1-customers--customer--plan">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost/api/v1/customers/1/plan" \
    --header "Authorization: Bearer {YOUR_AUTH_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"plan_id\": 2
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/v1/customers/1/plan"
);

const headers = {
    "Authorization": "Bearer {YOUR_AUTH_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "plan_id": 2
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-customers--customer--plan">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;legalName&quot;: &quot;Cliente Ejemplo S.A. de C.V.&quot;,
        &quot;status&quot;: &quot;active&quot;,
        &quot;currentPlan&quot;: {
            &quot;id&quot;: 2,
            &quot;planCode&quot;: &quot;PREMIUM&quot;,
            &quot;planName&quot;: &quot;Plan Premium&quot;
        },
        &quot;createdAt&quot;: &quot;2026-09-23T06:00:00+00:00&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (400):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;El cliente ya tiene asignado este plan&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;The selected plan id is invalid.&quot;,
    &quot;errors&quot;: {
        &quot;plan_id&quot;: [
            &quot;The selected plan id is invalid.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-customers--customer--plan" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-customers--customer--plan"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-customers--customer--plan"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-customers--customer--plan" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-customers--customer--plan">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-customers--customer--plan" data-method="POST"
      data-path="api/v1/customers/{customer}/plan"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-customers--customer--plan', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-customers--customer--plan"
                    onclick="tryItOut('POSTapi-v1-customers--customer--plan');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-customers--customer--plan"
                    onclick="cancelTryOut('POSTapi-v1-customers--customer--plan');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-customers--customer--plan"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/customers/{customer}/plan</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-customers--customer--plan"
               value="Bearer {YOUR_AUTH_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_AUTH_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-customers--customer--plan"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-customers--customer--plan"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>customer</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="customer"                data-endpoint="POSTapi-v1-customers--customer--plan"
               value="1"
               data-component="url">
    <br>
<p>ID del cliente. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>plan_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="plan_id"                data-endpoint="POSTapi-v1-customers--customer--plan"
               value="2"
               data-component="body">
    <br>
<p>ID del plan de suscripción a asignar. Example: <code>2</code></p>
        </div>
        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
