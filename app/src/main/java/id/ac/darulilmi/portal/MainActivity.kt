package id.ac.darulilmi.portal

import android.annotation.SuppressLint
import android.content.Intent
import android.content.ActivityNotFoundException
import android.net.Uri
import android.os.Bundle
import android.webkit.WebChromeClient
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
        window.statusBarColor = android.graphics.Color.rgb(8, 14, 23)
        window.navigationBarColor = android.graphics.Color.rgb(8, 14, 23)

        setContent {
            var loading by remember { mutableStateOf(true) }
            var pageError by remember { mutableStateOf<String?>(null) }
            var webViewRef by remember { mutableStateOf<WebView?>(null) }

            BackHandler(enabled = webViewRef?.canGoBack() == true) {
                webViewRef?.goBack()
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
                                    cacheMode = WebSettings.LOAD_NO_CACHE
                                    loadWithOverviewMode = true
                                    useWideViewPort = true
                                    javaScriptCanOpenWindowsAutomatically = false
                                    setSupportMultipleWindows(false)
                                    mixedContentMode = WebSettings.MIXED_CONTENT_NEVER_ALLOW
                                    allowFileAccess = false
                                    allowContentAccess = true
                                }

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

                                webViewClient = object : WebViewClient() {
                                    override fun shouldOverrideUrlLoading(
                                        view: WebView,
                                        request: WebResourceRequest
                                    ): Boolean {
                                        val uri = request.url
                                        if (uri.scheme == "https" && uri.host == CAMPUS_HOST) {
                                            return false
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
