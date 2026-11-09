package id.ac.darulilmi.portal

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

private val Forest = Color(0xFF087A4B)
private val Emerald = Color(0xFF119B62)
private val Mint = Color(0xFFE8F6EE)
private val Lime = Color(0xFFB8DE78)
private val Gold = Color(0xFFD9B64D)
private val Ink = Color(0xFF173B2B)
private val CanvasBg = Color(0xFFF5FAF6)

data class CampusData(
    val name: String = "STIT Darul Ilmi Tasikmalaya",
    val tagline: String = "Membangun generasi berilmu dan berakhlak",
    val about: String = "Portal informasi resmi kampus. Konten pada aplikasi ini dapat diperbarui melalui Web Admin.",
    val address: String = "Tasikmalaya, Jawa Barat",
    val programs: List<String> = listOf("S1 Manajemen Pendidikan Islam"),
    val news: List<String> = emptyList()
)

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { MaterialTheme(colorScheme = lightColorScheme(primary = Forest, secondary = Gold, background = CanvasBg, surface = Color.White)) { PortalApp() } }
    }
}

@Composable
private fun PortalApp() {
    var data by remember { mutableStateOf(CampusData()) }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var showLogin by remember { mutableStateOf(false) }
    var username by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var token by remember { mutableStateOf<String?>(null) }
    var role by remember { mutableStateOf("") }
    var userName by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        loading = true
        try {
            data = withContext(Dispatchers.IO) { loadPublicHome() }
            error = null
        } catch (e: Exception) { error = "Konten belum dapat dimuat. Periksa alamat API dan koneksi internet." }
        loading = false
    }

    Scaffold(
        containerColor = CanvasBg,
        topBar = {
            TopAppBarCompat(title = data.name, onLogin = { showLogin = true }, loggedIn = token != null)
        }
    ) { padding ->
        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(padding),
            contentPadding = PaddingValues(18.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            item {
                Card(shape = RoundedCornerShape(28.dp), colors = CardDefaults.cardColors(containerColor = Forest), elevation = CardDefaults.cardElevation(defaultElevation = 5.dp)) {
                    Column(Modifier.fillMaxWidth().padding(22.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(Modifier.size(64.dp).background(Color.White, RoundedCornerShape(20.dp)), contentAlignment = Alignment.Center) {
                                Column(horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.Center) {
                                    Icon(Icons.Default.MenuBook, null, tint = Forest, modifier = Modifier.size(31.dp))
                                    Text("STIT", color = Forest, fontSize = 8.sp, fontWeight = FontWeight.Black)
                                }
                            }
                            Spacer(Modifier.width(14.dp))
                            Column {
                                Text("SEKOLAH TINGGI ILMU TARBIYAH", color = Lime, fontSize = 9.sp, fontWeight = FontWeight.Bold)
                                Text("DARUL ILMI", color = Color.White, fontSize = 21.sp, fontWeight = FontWeight.ExtraBold)
                            }
                        }
                        Text(data.tagline, color = Color.White, fontSize = 25.sp, lineHeight = 31.sp, fontWeight = FontWeight.Bold)
                        Text("Jelajahi informasi kampus, program studi, berita, dan layanan akademik dalam satu aplikasi.", color = Color.White.copy(alpha = .88f), lineHeight = 21.sp)
                        Button(onClick = { showLogin = true }, shape = RoundedCornerShape(14.dp), colors = ButtonDefaults.buttonColors(containerColor = Lime), contentPadding = PaddingValues(horizontal = 20.dp, vertical = 12.dp)) {
                            Text(if (token == null) "Login Civitas Akademika" else "Akun: $userName", color = Ink, fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
            item {
                SectionTitle("Layanan publik")
                Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.fillMaxWidth()) {
                    PublicTile("Profil", Icons.Default.AccountBalance, Modifier.weight(1f))
                    PublicTile("Program Studi", Icons.Default.School, Modifier.weight(1f))
                    PublicTile("PMB", Icons.Default.HowToReg, Modifier.weight(1f))
                }
            }
            item { SectionTitle("Tentang kampus"); Text(data.about, color = Color(0xFF52665B), lineHeight = 23.sp) }
            item { SectionTitle("Program studi") }
            items(data.programs) { name -> SimpleInfoCard(name, "Program studi aktif") }
            item { SectionTitle("Berita dan pengumuman") }
            if (loading) item { LinearProgressIndicator(Modifier.fillMaxWidth()) }
            if (data.news.isEmpty()) item { SimpleInfoCard("Belum ada berita", "Berita yang dipublikasikan Admin akan tampil di sini.") }
            items(data.news) { title -> SimpleInfoCard(title, "Berita kampus") }
            item { SimpleInfoCard("Lokasi kampus", data.address) }
            if (error != null) item { Text(error!!, color = MaterialTheme.colorScheme.error) }
            if (message != null) item { Text(message!!, color = Navy) }
            if (token != null) {
                item {
                    SectionTitle("Layanan akun $role")
                    val modules = when (role.lowercase()) {
                        "mahasiswa" -> listOf("KRS", "KHS dan IPK", "Jadwal kuliah", "Nilai", "Tagihan")
                        "dosen" -> listOf("Jadwal mengajar", "Daftar kelas", "Presensi", "Input nilai")
                        else -> listOf("Dashboard akademik", "Laporan program studi", "Ringkasan akademik")
                    }
                    modules.forEach { SimpleInfoCard(it, "Modul prototipe — integrasi data tahap berikutnya") }
                    OutlinedButton(onClick = { token = null; role = ""; userName = ""; message = "Anda telah keluar." }) { Text("Keluar") }
                }
            }
        }
    }

    if (showLogin) {
        AlertDialog(
            onDismissRequest = { showLogin = false },
            title = { Text("Login civitas akademika") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text("Pengunjung publik tidak perlu login. Login hanya untuk mahasiswa, dosen, dan pimpinan.")
                    OutlinedTextField(username, { username = it }, label = { Text("Username") }, singleLine = true)
                    OutlinedTextField(password, { password = it }, label = { Text("Password") }, singleLine = true)
                }
            },
            confirmButton = {
                TextButton(onClick = {
                    loading = true
                    message = null
                    // Login is executed off the UI thread; the token is held only for this prototype session.
                    kotlinx.coroutines.MainScope().launch {
                        try {
                            val result = withContext(Dispatchers.IO) { login(username.trim(), password) }
                            token = result.first
                            userName = result.second
                            role = result.third
                            showLogin = false
                            password = ""
                            message = "Login berhasil."
                        } catch (e: Exception) { message = e.message ?: "Login gagal." }
                        loading = false
                    }
                }, enabled = username.isNotBlank() && password.isNotBlank() && !loading) { Text("Masuk") }
            },
            dismissButton = { TextButton(onClick = { showLogin = false }) { Text("Batal") } }
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun TopAppBarCompat(title: String, onLogin: () -> Unit, loggedIn: Boolean) {
    TopAppBar(
        title = { Text(title, maxLines = 1, fontWeight = FontWeight.Bold, color = Forest, fontSize = 16.sp) },
        colors = TopAppBarDefaults.topAppBarColors(containerColor = Color(0xFFF5FAF6)),
        actions = { TextButton(onClick = onLogin) { Text(if (loggedIn) "Akun" else "Login", color = Forest, fontWeight = FontWeight.Bold) } }
    )
}

@Composable
private fun SectionTitle(text: String) { Text(text, fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Navy) }

@Composable
private fun PublicTile(title: String, icon: androidx.compose.ui.graphics.vector.ImageVector, modifier: Modifier) {
    Card(modifier, shape = RoundedCornerShape(18.dp), colors = CardDefaults.cardColors(containerColor = Color.White), border = BorderStroke(1.dp, Color(0xFFE1EEE5)), elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)) {
        Column(Modifier.fillMaxWidth().padding(vertical = 15.dp), horizontalAlignment = Alignment.CenterHorizontally) {
            Icon(icon, null, tint = Forest, modifier = Modifier.size(25.dp))
            Spacer(Modifier.height(6.dp))
            Text(title, fontSize = 11.sp, fontWeight = FontWeight.SemiBold, color = Ink)
        }
    }
}

@Composable
private fun SimpleInfoCard(title: String, subtitle: String) {
    Card(shape = RoundedCornerShape(18.dp), colors = CardDefaults.cardColors(containerColor = Color.White), border = BorderStroke(1.dp, Color(0xFFE1EEE5)), elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            Text(title, fontWeight = FontWeight.Bold, color = Ink)
            Spacer(Modifier.height(4.dp))
            Text(subtitle, color = Color(0xFF66796D), fontSize = 13.sp)
        }
    }
}

private fun loadPublicHome(): CampusData {
    val base = BuildConfig.API_BASE_URL.trimEnd('/')
    val json = request("$base/api/v1/public/home", "GET", null, null)
    val campus = json.optJSONObject("campus") ?: JSONObject()
    val programsJson = json.optJSONArray("programs")
    val newsJson = json.optJSONArray("news")
    val programs = (0 until (programsJson?.length() ?: 0)).mapNotNull { i -> programsJson?.optJSONObject(i)?.optString("name") }
    val news = (0 until (newsJson?.length() ?: 0)).mapNotNull { i -> newsJson?.optJSONObject(i)?.optString("title") }
    return CampusData(
        name = campus.optString("name", "STIT Darul Ilmi Tasikmalaya"),
        tagline = campus.optString("tagline").ifBlank { "Membangun generasi berilmu dan berakhlak" },
        about = campus.optString("about").ifBlank { "Portal informasi resmi kampus." },
        address = campus.optString("address").ifBlank { "Tasikmalaya, Jawa Barat" },
        programs = programs.ifEmpty { listOf("Informasi program studi akan segera tersedia.") },
        news = news
    )
}

private fun login(username: String, password: String): Triple<String, String, String> {
    val base = BuildConfig.API_BASE_URL.trimEnd('/')
    val body = JSONObject().put("username", username).put("password", password)
    val json = request("$base/api/v1/auth/login", "POST", body.toString(), null)
    if (!json.optBoolean("success")) throw IllegalStateException(json.optString("message", "Login gagal."))
    val user = json.optJSONObject("user") ?: JSONObject()
    return Triple(json.getString("token"), user.optString("name"), user.optString("role"))
}

private fun request(url: String, method: String, body: String?, bearer: String?): JSONObject {
    val connection = (URL(url).openConnection() as HttpURLConnection)
    connection.requestMethod = method
    connection.connectTimeout = 12000
    connection.readTimeout = 12000
    connection.setRequestProperty("Accept", "application/json")
    if (body != null) {
        connection.doOutput = true
        connection.setRequestProperty("Content-Type", "application/json; charset=utf-8")
        connection.outputStream.use { it.write(body.toByteArray(Charsets.UTF_8)) }
    }
    if (bearer != null) connection.setRequestProperty("Authorization", "Bearer $bearer")
    val status = connection.responseCode
    val stream = if (status in 200..299) connection.inputStream else connection.errorStream
    val text = stream?.bufferedReader()?.use { it.readText() }.orEmpty()
    connection.disconnect()
    val json = runCatching { JSONObject(text) }.getOrElse { JSONObject() }
    if (status !in 200..299) throw IllegalStateException(json.optString("message", "Server merespons HTTP $status"))
    return json
}
