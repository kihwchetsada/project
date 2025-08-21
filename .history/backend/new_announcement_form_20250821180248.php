<?php
session_start();

// ✅ เช็กว่าเป็น organizer หรือไม่
if (!isset($_SESSION['userData']) || $_SESSION['userData']['role'] !== 'organizer') {
    die('❌ คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เพิ่มประกาศใหม่</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../img/logo.jpg">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'sarabun': ['Sarabun', 'sans-serif'],
          },
          animation: {
            'fade-in': 'fadeIn 0.6s ease-out',
            'slide-up': 'slideUp 0.6s ease-out',
            'pulse-slow': 'pulse 2s infinite',
            'bounce-gentle': 'bounceGentle 1s infinite',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0' },
              '100%': { opacity: '1' }
            },
            slideUp: {
              '0%': { opacity: '0', transform: 'translateY(30px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' }
            },
            bounceGentle: {
              '0%, 100%': { transform: 'translateY(0)' },
              '50%': { transform: 'translateY(-10px)' }
            }
          }
        }
      }
    }
  </script>
</head>
<body class="font-sarabun min-h-screen bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-800 py-8 px-4">
  
  <!-- Main Container -->
  <div class="max-w-2xl mx-auto">
    
    <!-- Header Section -->
    <div class="text-center mb-8 animate-fade-in">
      <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full shadow-2xl mb-6 border border-white/30">
        <i class="fas fa-bullhorn text-3xl text-white animate-bounce-gentle"></i>
      </div>
      <h1 class="text-4xl font-bold text-white mb-3 drop-shadow-lg">เพิ่มประกาศใหม่</h1>
      <p class="text-blue-100 text-lg">สร้างประกาศสำคัญสำหรับสมาชิกของคุณ</p>
    </div>

    <!-- Form Container -->
    <div class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl p-8 border border-white/20 animate-slide-up">
      <form action="create_announcement.php" method="POST" enctype="multipart/form-data" class="space-y-8">
        
        <!-- Title Field -->
        <div class="group">
          <label class="flex items-center text-gray-700 font-semibold mb-3">
            <i class="fas fa-heading text-blue-600 mr-2"></i>
            หัวข้อประกาศ
          </label>
          <input type="text" name="title" required 
                class="w-full border-2 border-gray-200 rounded-xl px-4 py-4 text-lg transition-all duration-300 
                        focus:border-blue-500 focus:ring-4 focus:ring-blue-200 focus:outline-none 
                        hover:border-gray-300 group-hover:shadow-lg">
        </div>

        <!-- Description Field -->
        <div class="group">
          <label class="flex items-center text-gray-700 font-semibold mb-3">
            <i class="fas fa-align-left text-blue-600 mr-2"></i>
            รายละเอียด
          </label>
          <textarea name="description" rows="5" required 
                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-4 text-lg resize-none transition-all duration-300 
                          focus:border-blue-500 focus:ring-4 focus:ring-blue-200 focus:outline-none 
                          hover:border-gray-300 group-hover:shadow-lg"></textarea>
        </div>

        <!-- Category Field -->
        <div class="group">
          <label class="flex items-center text-gray-700 font-semibold mb-3">
            <i class="fas fa-tag text-blue-600 mr-2"></i>
            หมวดหมู่
          </label>
          <input type="text" name="category" required 
                class="w-full border-2 border-gray-200 rounded-xl px-4 py-4 text-lg transition-all duration-300 
                        focus:border-blue-500 focus:ring-4 focus:ring-blue-200 focus:outline-none 
                        hover:border-gray-300 group-hover:shadow-lg">
        </div>

        <!-- Priority Field -->
        <div>
          <label class="flex items-center text-gray-700 font-semibold mb-4">
            <i class="fas fa-exclamation-circle text-blue-600 mr-2"></i>
            ระดับความสำคัญ
          </label>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- High Priority -->
            <input type="radio" name="priority" value="สูง" id="high" class="hidden peer/high" required>
            <label for="high" class="peer-checked/high:ring-4 peer-checked/high:ring-red-300 peer-checked/high:scale-105
                                  bg-gradient-to-r from-red-500 to-red-600 text-white text-center py-4 rounded-xl 
                                  cursor-pointer transition-all duration-300 hover:scale-105 hover:shadow-xl 
                                  font-semibold text-lg shadow-lg">
              <i class="fas fa-exclamation-triangle text-xl mb-2 block"></i>
              สูง
            </label>
            
            <!-- Medium Priority -->
            <input type="radio" name="priority" value="ปานกลาง" id="medium" class="hidden peer/medium">
            <label for="medium" class="peer-checked/medium:ring-4 peer-checked/medium:ring-yellow-300 peer-checked/medium:scale-105
                                  bg-gradient-to-r from-yellow-500 to-orange-500 text-white text-center py-4 rounded-xl 
                                    cursor-pointer transition-all duration-300 hover:scale-105 hover:shadow-xl 
                                    font-semibold text-lg shadow-lg">
              <i class="fas fa-minus-circle text-xl mb-2 block"></i>
              ปานกลาง
            </label>
            
            <!-- Low Priority -->
            <input type="radio" name="priority" value="ต่ำ" id="low" class="hidden peer/low">
            <label for="low" class="peer-checked/low:ring-4 peer-checked/low:ring-green-300 peer-checked/low:scale-105
                                  bg-gradient-to-r from-green-500 to-emerald-500 text-white text-center py-4 rounded-xl 
                                  cursor-pointer transition-all duration-300 hover:scale-105 hover:shadow-xl 
                                  font-semibold text-lg shadow-lg">
              <i class="fas fa-info-circle text-xl mb-2 block"></i>
              ต่ำ
            </label>
          </div>
        </div>

        <!-- Image Upload -->
        <div>
          <label class="flex items-center text-gray-700 font-semibold mb-4">
            <i class="fas fa-image text-blue-600 mr-2"></i>
            เลือกรูปภาพ
          </label>
          <div class="relative">
            <input type="file" name="image" accept="image/*" id="imageInput" class="hidden" onchange="showFileName(this)">
            <label for="imageInput" class="block w-full border-2 border-dashed border-gray-300 rounded-xl p-8 text-center 
                                          cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all duration-300 
                                          group bg-gray-50">
              <div class="group-hover:animate-pulse-slow">
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 group-hover:text-blue-500 transition-colors duration-300 mb-4"></i>
                <p class="text-gray-600 text-lg font-medium mb-2">คลิกเพื่ออัปโหลดรูปภาพ</p>
                <p class="text-gray-400">รองรับไฟล์ JPG, PNG, GIF (ขนาดสูงสุด 5MB)</p>
              </div>
            </label>
          </div>
          <div id="fileName" class="mt-3 text-green-600 font-medium hidden flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span></span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 pt-8">
          <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 
                                      text-white py-4 px-8 rounded-xl font-semibold text-lg shadow-lg 
                                      transition-all duration-300 hover:scale-105 hover:shadow-xl 
                                      transform active:scale-95 flex items-center justify-center">
            <i class="fas fa-save mr-3"></i>
            บันทึกประกาศ
          </button>
          
          <a href="organizer_dashboard.php" class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 
                                        text-white py-4 px-8 rounded-xl font-semibold text-lg shadow-lg 
                                        transition-all duration-300 hover:scale-105 hover:shadow-xl 
                                        transform active:scale-95 flex items-center justify-center">
            <i class="fas fa-arrow-left mr-3"></i>
            ย้อนกลับ
          </a>
        </div>

        <!-- Form Footer -->
        <div class="text-center pt-4 border-t border-gray-200">
          <p class="text-gray-500 text-sm">
            <i class="fas fa-info-circle mr-1"></i>
            กรุณาตรวจสอบข้อมูลให้ถูกต้องก่อนบันทึก
          </p>
        </div>
      </form>
    </div>

    <!-- Additional Info -->
    <div class="text-center mt-8 animate-fade-in">
      <div class="inline-flex items-center bg-white/20 backdrop-blur-sm rounded-full px-6 py-3 text-white border border-white/30">
        <i class="fas fa-lightbulb text-yellow-300 mr-2"></i>
        <span class="text-sm">เคล็ดลับ: ใช้หัวข้อที่ชัดเจนและรายละเอียดที่เข้าใจง่าย</span>
      </div>
    </div>
  </div>

  <script>
    // Show selected file name
    function showFileName(input) {
      const fileName = document.getElementById('fileName');
      const fileNameSpan = fileName.querySelector('span');
      
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        
        fileNameSpan.textContent = `${file.name} (${fileSize} MB)`;
        fileName.classList.remove('hidden');
        
        // Add success animation
        fileName.classList.add('animate-bounce-gentle');
        setTimeout(() => {
          fileName.classList.remove('animate-bounce-gentle');
        }, 1000);
      } else {
        fileName.classList.add('hidden');
      }
    }

    // Form validation feedback
    document.querySelectorAll('input[required], textarea[required]').forEach(element => {
      element.addEventListener('blur', function() {
        if (this.value.trim() === '') {
          this.classList.add('border-red-300', 'bg-red-50');
          this.classList.remove('border-gray-200');
        } else {
          this.classList.remove('border-red-300', 'bg-red-50');
          this.classList.add('border-green-300', 'bg-green-50');
        }
      });
      
      element.addEventListener('input', function() {
        if (this.value.trim() !== '') {
          this.classList.remove('border-red-300', 'bg-red-50');
          this.classList.add('border-green-300', 'bg-green-50');
        }
      });
    });

    // Smooth scroll animation on form submit
    document.querySelector('form').addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>กำลังบันทึก...';
      submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
    });

    // Add loading states to inputs
    document.querySelectorAll('input, textarea').forEach(element => {
      element.addEventListener('focus', function() {
        this.closest('.group')?.classList.add('scale-105');
      });
      
      element.addEventListener('blur', function() {
        this.closest('.group')?.classList.remove('scale-105');
      });
    });
  </script>
</body>
</html>