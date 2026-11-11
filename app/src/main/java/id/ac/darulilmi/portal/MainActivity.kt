package id.ac.darulilmi.portal

import android.annotation.SuppressLint
import android.app.DownloadManager
import android.content.Context
import android.os.Environment
import android.webkit.CookieManager
import android.webkit.URLUtil
import android.widget.Toast
import android.content.Intent
import android.content.ActivityNotFoundException
import android.net.Uri
import android.print.PrintAttributes
import android.print.PrintManager
import android.os.Bundle
import android.os.Build
import android.webkit.WebChromeClient
import android.webkit.JavascriptInterface
import android.webkit.ValueCallback
import android.webkit.WebResourceRequest
import android.webkit.WebResourceError
import android.webkit.WebResourceResponse
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.ComponentActivity
import androidx.activity.result.ActivityResultLauncher
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.compose.BackHandler
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Button
import androidx.compose.material3.Text
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.viewinterop.AndroidView

private const val CAMPUS_WEBSITE_URL = "https://siakadstitdit.byethost24.com/website/"
private const val CAMPUS_HOST = "siakadstitdit.byethost24.com"
private val CampusGreen = Color(0xFF245C3A)

class MainActivity : ComponentActivity() {
    private var campusWebView: WebView? = null
    private var pendingFileCallback: ValueCallback<Array<Uri>>? = null
    private lateinit var fileChooserLauncher: ActivityResultLauncher<Intent>

    private inner class AndroidPrintBridge {
        @JavascriptInterface
        fun printPage() {
            runOnUiThread {
                val view = campusWebView ?: return@runOnUiThread
                runCatching {
                    val printManager = getSystemService(Context.PRINT_SERVICE) as PrintManager
                    val adapter = view.createPrintDocumentAdapter("STIT Darul Ilmi")
                    printManager.print(
                        "STIT Darul Ilmi",
                        adapter,
                        PrintAttributes.Builder().build()
                    )
                }.onFailure {
                    Toast.makeText(this@MainActivity, "Fitur cetak tidak dapat dibuka.", Toast.LENGTH_LONG).show()
                }
            }
        }
    }

    private fun deliverSelectedFiles(data: Intent?) {
        val callback = pendingFileCallback ?: return
        pendingFileCallback = null
        val results = mutableListOf<Uri>()
        data?.clipData?.let { clip ->
            for (index in 0 until clip.itemCount) {
                results.add(clip.getItemAt(index).uri)
            }
        }
        if (results.isEmpty()) data?.data?.let(results::add)
        callback.onReceiveValue(results.distinct().takeIf { it.isNotEmpty() }?.toTypedArray())
    }

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        fileChooserLauncher = registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            if (result.resultCode == RESULT_OK) deliverSelectedFiles(result.data)
            else deliverSelectedFiles(null)
        }
        window.statusBarColor = android.graphics.Color.rgb(0, 48, 35)
        window.navigationBarColor = android.graphics.Color.rgb(0, 30, 23)
        @Suppress("DEPRECATION")
        window.decorView.systemUiVisibility = 0

        setContent {
            var loading by remember { mutableStateOf(true) }
            var pageError by remember { mutableStateOf<String?>(null) }
            var webViewRef by remember { mutableStateOf<WebView?>(null) }
            var canNavigateBack by remember { mutableStateOf(false) }

            BackHandler(enabled = canNavigateBack) {
                val view = webViewRef
                if (view?.canGoBack() == true) {
                    view.goBack()
                } else {
                    finish()
                }
            }

            MaterialTheme(colorScheme = lightColorScheme(primary = CampusGreen)) {
                Box(Modifier.fillMaxSize()) {
                    AndroidView(
                        modifier = Modifier.fillMaxSize(),
                        factory = { context ->
                            WebView(context).apply {
                                campusWebView = this
                                webViewRef = this
                                setBackgroundColor(android.graphics.Color.WHITE)

                                settings.apply {
                                    javaScriptEnabled = true
                                    domStorageEnabled = true
                                    databaseEnabled = true
                                    cacheMode = WebSettings.LOAD_DEFAULT
                                    loadWithOverviewMode = true
                                    useWideViewPort = true
                                    javaScriptCanOpenWindowsAutomatically = false
                                    setSupportMultipleWindows(false)
                                    mixedContentMode = WebSettings.MIXED_CONTENT_NEVER_ALLOW
                                    allowFileAccess = false
                                    allowContentAccess = true
                                }
                                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                                    settings.safeBrowsingEnabled = true
                                }
                                CookieManager.getInstance().apply {
                                    setAcceptCookie(true)
                                }
                                CookieManager.getInstance().setAcceptThirdPartyCookies(this, false)

                                webChromeClient = object : WebChromeClient() {
                                    override fun onProgressChanged(view: WebView?, newProgress: Int) {
                                        loading = newProgress < 100
                                    }

                                    override fun onShowFileChooser(
                                        webView: WebView?,
                                        filePathCallback: ValueCallback<Array<Uri>>?,
                                        fileChooserParams: WebChromeClient.FileChooserParams?
                                    ): Boolean {
                                        pendingFileCallback?.onReceiveValue(null)
                                        pendingFileCallback = filePathCallback ?: return false

                                        val acceptedTypes = fileChooserParams?.acceptTypes
                                            ?.flatMap { it.split(",") }
                                            ?.map { it.trim() }
                                            ?.filter { it.isNotEmpty() && it != "*/*" }
                                            ?.distinct()
                                            .orEmpty()
                                        val mimeTypes = acceptedTypes.mapNotNull { type ->
                                            when {
                                                type.startsWith(".") -> when (type.lowercase()) {
                                                    ".jpg", ".jpeg" -> "image/jpeg"
                                                    ".png" -> "image/png"
                                                    ".webp" -> "image/webp"
                                                    ".pdf" -> "application/pdf"
                                                    else -> null
                                                }
                                                '/' in type -> type
                                                else -> null
                                            }
                                        }.distinct()

                                        val openIntent = Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
                                            addCategory(Intent.CATEGORY_OPENABLE)
                                            type = if (mimeTypes.size == 1) mimeTypes.first() else "*/*"
                                            if (mimeTypes.size > 1) putExtra(Intent.EXTRA_MIME_TYPES, mimeTypes.toTypedArray())
                                            putExtra(Intent.EXTRA_ALLOW_MULTIPLE, fileChooserParams?.mode == WebChromeClient.FileChooserParams.MODE_OPEN_MULTIPLE)
                                        }
                                        return try {
                                            fileChooserLauncher.launch(openIntent)
                                            true
                                        } catch (_: ActivityNotFoundException) {
                                            try {
                                                val fallback = Intent(Intent.ACTION_GET_CONTENT).apply {
                                                    addCategory(Intent.CATEGORY_OPENABLE)
                                                    type = if (mimeTypes.size == 1) mimeTypes.first() else "*/*"
                                                    if (mimeTypes.size > 1) putExtra(Intent.EXTRA_MIME_TYPES, mimeTypes.toTypedArray())
                                                    putExtra(Intent.EXTRA_ALLOW_MULTIPLE, fileChooserParams?.mode == WebChromeClient.FileChooserParams.MODE_OPEN_MULTIPLE)
                                                }
                                                fileChooserLauncher.launch(fallback)
                                                true
                                            } catch (_: Exception) {
                                                pendingFileCallback?.onReceiveValue(null)
                                                pendingFileCallback = null
                                                false
                                            }
                                        } catch (_: Exception) {
                                            pendingFileCallback?.onReceiveValue(null)
                                            pendingFileCallback = null
                                            false
                                        }
                                    }
                                }

                                // Expose only a narrow print bridge to pages loaded from the trusted campus host.
                                addJavascriptInterface(AndroidPrintBridge(), "AndroidPrint")

                                setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
                                    val downloadUri = runCatching { Uri.parse(url) }.getOrNull()
                                    if (downloadUri == null || downloadUri.scheme != "https") {
                                        Toast.makeText(context, "Unduhan hanya diizinkan melalui koneksi HTTPS.", Toast.LENGTH_LONG).show()
                                    } else if (downloadUri.host.equals(CAMPUS_HOST, ignoreCase = true)) {
                                        runCatching {
                                            val request = DownloadManager.Request(downloadUri).apply {
                                                setMimeType(mimeType)
                                                addRequestHeader("User-Agent", userAgent.orEmpty())
                                                CookieManager.getInstance().getCookie(url)?.let { addRequestHeader("Cookie", it) }
                                                setTitle(URLUtil.guessFileName(url, contentDisposition, mimeType))
                                                setDescription("Mengunduh dokumen akademik")
                                                setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                                                setDestinationInExternalPublicDir(
                                                    Environment.DIRECTORY_DOWNLOADS,
                                                    URLUtil.guessFileName(url, contentDisposition, mimeType)
                                                )
                                                allowScanningByMediaScanner()
                                            }
                                            val manager = this@MainActivity.getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
                                            manager.enqueue(request)
                                            Toast.makeText(context, "Unduhan dimulai. Periksa folder Download.", Toast.LENGTH_LONG).show()
                                        }.onFailure {
                                            Toast.makeText(context, "Tidak dapat memulai unduhan: ${it.localizedMessage ?: "kesalahan sistem"}", Toast.LENGTH_LONG).show()
                                        }
                                    } else {
                                        runCatching { startActivity(Intent(Intent.ACTION_VIEW, downloadUri)) }
                                            .onFailure { Toast.makeText(context, "Tidak ada aplikasi untuk mengunduh tautan ini.", Toast.LENGTH_LONG).show() }
                                    }
                                }

                                webViewClient = object : WebViewClient() {
                                    override fun shouldOverrideUrlLoading(
                                        view: WebView,
                                        request: WebResourceRequest
                                    ): Boolean {
                                        val uri = request.url
                                        if (uri.scheme == "https" && uri.host.equals(CAMPUS_HOST, ignoreCase = true)) {
                                            return false
                                        }
                                        if (uri.scheme == "http" && uri.host.equals(CAMPUS_HOST, ignoreCase = true)) {
                                            view.loadUrl(uri.buildUpon().scheme("https").build().toString())
                                            return true
                                        }
                                        if (uri.scheme == "http" || uri.scheme == "https" ||
                                            uri.scheme == "mailto" || uri.scheme == "tel"
                                        ) {
                                            return try {
                                                startActivity(Intent(Intent.ACTION_VIEW, uri))
                                                true
                                            } catch (_: Exception) {
                                                true
                                            }
                                        }
                                        return true
                                    }

                                    override fun onPageStarted(
                                        view: WebView?,
                                        url: String?,
                                        favicon: android.graphics.Bitmap?
                                    ) {
                                        loading = true
                                        pageError = null
                                        canNavigateBack = view?.canGoBack() == true
                                    }

                                    override fun onReceivedHttpError(
                                        view: WebView?,
                                        request: WebResourceRequest?,
                                        errorResponse: WebResourceResponse?
                                    ) {
                                        if (request?.isForMainFrame == true) {
                                            pageError = "Server mengembalikan HTTP ${errorResponse?.statusCode ?: "error"} saat membuka halaman. Periksa konfigurasi PHP/database hosting."
                                            loading = false
                                        }
                                    }

                                    override fun onReceivedError(
                                        view: WebView?,
                                        request: WebResourceRequest?,
                                        error: WebResourceError?
                                    ) {
                                        if (request?.isForMainFrame == true) {
                                            pageError = "Halaman tidak dapat dihubungi: ${error?.description ?: "koneksi gagal"}. Periksa koneksi internet atau server hosting."
                                            loading = false
                                        }
                                    }

                                    override fun onPageFinished(view: WebView?, url: String?) {
                                        loading = false
                                        canNavigateBack = view?.canGoBack() == true
                                        if (url != null && Uri.parse(url).host.equals(CAMPUS_HOST, ignoreCase = true)) {
                                            view?.evaluateJavascript(
                                                "(function(){window.print=function(){if(window.AndroidPrint){AndroidPrint.printPage();}};})();",
                                                null
                                            )
                                        }
                                    }
                                }

                                clearCache(true)
                                clearHistory()
                                loadUrl(CAMPUS_WEBSITE_URL)
                            }
                        },
                        update = { view ->
                            if (webViewRef !== view) webViewRef = view
                        }
                    )

                    if (loading) {
                        CircularProgressIndicator(
                            modifier = Modifier.align(Alignment.Center),
                            color = CampusGreen
                        )
                    }
                    if (pageError != null) {
                        Column(
                            modifier = Modifier.align(Alignment.Center).fillMaxWidth().padding(24.dp),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text("Layanan kampus belum dapat dibuka", color = CampusGreen, fontWeight = FontWeight.Bold)
                            Text(pageError!!, modifier = Modifier.padding(top = 10.dp, bottom = 16.dp))
                            Button(onClick = { pageError = null; loading = true; webViewRef?.reload() }) {
                                Text("Coba Lagi")
                            }
                        }
                    }
                }
            }
        }
    }

    @Deprecated("Deprecated in Java")
    override fun onBackPressed() {
        val view = campusWebView
        if (view?.canGoBack() == true) view.goBack() else super.onBackPressed()
    }

    override fun onDestroy() {
        pendingFileCallback?.onReceiveValue(null)
        pendingFileCallback = null
        campusWebView?.apply {
            stopLoading()
            webChromeClient = null
            webViewClient = WebViewClient()
            destroy()
        }
        campusWebView = null
        super.onDestroy()
    }
}
