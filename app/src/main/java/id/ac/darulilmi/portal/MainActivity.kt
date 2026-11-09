package id.ac.darulilmi.portal

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

private val Navy = Color(0xFF142B4A)
private val Gold = Color(0xFFC79B45)
private val CanvasBg = Color(0xFFF4F6FA)

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
        setContent { MaterialTheme(colorScheme = lightColorScheme(primary = Navy, secondary = Gold)) { PortalApp() } }
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
                Card(shape = RoundedCornerShape(24.dp), colors = CardDefaults.cardColors(containerColor = Navy)) {
                    Column(Modifier.fillMaxWidth().padding(22.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(Modifier.size(58.dp).background(Color.White, RoundedCornerShape(18.dp)), contentAlignment = Alignment.Center) {
                                Text("DI", color = Navy, fontSize = 24.sp, fontWeight = FontWeight.Black)
                            }
                            Spacer(Modifier.width(14.dp))
                            Column {
                                Text("PORTAL AKADEMIK", color = Gold, fontSize = 11.sp, fontWeight = FontWeight.Bold)
                                Text("STIT DARUL ILMI", color = Color.White, fontSize = 19.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                        Text(data.tagline, color = Color.White, fontSize = 23.sp, fontWeight = FontWeight.Bold)
                        Text("Informasi kampus dan layanan akademik dalam satu aplikasi.", color = Color.White.copy(alpha = .82f))
                        Button(onClick = { showLogin = true }, colors = ButtonDefaults.buttonColors(containerColor = Gold)) {
                            Text(if (token == null) "Login civitas akademika" else "Akun: $userName", color = Navy)
                        }
                    }
                }
            }
            item {
                SectionTitle("Layanan publik")
                Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    PublicTile("Profil", Icons.Default.AccountBalance, Modifier.weight(1f))
                    PublicTile("Program Studi", Icons.Default.School, Modifier.weight(1f))
                    PublicTile("PMB", Icons.Default.HowToReg, Modifier.weight(1f))
                }
            }
            item { SectionTitle("Tentang kampus"); Text(data.about, color = Color(0xFF475569)) }
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
        title = { Text(title, maxLines = 1, fontWeight = FontWeight.Bold) },
        actions = { TextButton(onClick = onLogin) { Text(if (loggedIn) "Akun" else "Login") } }
    )
}

@Composable
private fun SectionTitle(text: String) { Text(text, fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Navy) }

@Composable
private fun PublicTile(title: String, icon: androidx.compose.ui.graphics.vector.ImageVector, modifier: Modifier) {
    Card(modifier, shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.fillMaxWidth().padding(vertical = 15.dp), horizontalAlignment = Alignment.CenterHorizontally) {
            Icon(icon, null, tint = Gold)
            Spacer(Modifier.height(6.dp))
            Text(title, fontSize = 11.sp, fontWeight = FontWeight.SemiBold)
        }
    }
}

@Composable
private fun SimpleInfoCard(title: String, subtitle: String) {
    Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            Text(title, fontWeight = FontWeight.Bold, color = Navy)
            Spacer(Modifier.height(4.dp))
            Text(subtitle, color = Color(0xFF64748B), fontSize = 13.sp)
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
