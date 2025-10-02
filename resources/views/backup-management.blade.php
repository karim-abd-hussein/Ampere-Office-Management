<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة النسخ الاحتياطي</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
            margin-bottom: 20px;
        }
        .info-box {
            background: #fff;
            border-radius: 0.25rem;
            padding: 1rem;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        .navbar {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Simple Navigation -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-bolt me-2"></i>Generator App - النسخ الاحتياطي
            </a>
            <a href="{{ url('/') }}" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-1"></i>العودة للرئيسية
            </a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">إدارة النسخ الاحتياطي</h3>
                    </div>
                    <div class="card-body">
                        <!-- Backup Status -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">آخر نسخ احتياطي</span>
                                        <span class="info-box-number" id="lastBackupTime">
                                            {{ $lastBackup ?? 'لم يتم إنشاء نسخ احتياطي' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-sync"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">الحالة</span>
                                        <span class="info-box-number" id="backupStatus">
                                            جاري التحقق...
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Backup Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <button type="button" class="btn btn-primary btn-lg mb-3" id="createBackupBtn">
                                    <i class="fas fa-plus-circle mr-2"></i>
                                    إنشاء نسخ احتياطي الآن
                                </button>
                                
                                <button type="button" class="btn btn-info btn-lg mb-3" id="refreshStatusBtn">
                                    <i class="fas fa-redo mr-2"></i>
                                    تحديث الحالة
                                </button>
                            </div>
                        </div>

                        <!-- Progress Bar (Hidden by default) -->
                        <div class="row mt-3 d-none" id="progressSection">
                            <div class="col-12">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" 
                                         style="width: 100%" 
                                         aria-valuenow="100" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        جاري إنشاء النسخ الاحتياطي...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Results Section -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div id="backupResult"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // Check initial status
        // checkBackupStatus();

        // Create backup button click
        $('#createBackupBtn').click(function() {
            createBackup();
        });

        // Refresh status button click
        // $('#refreshStatusBtn').click(function() {
        //     checkBackupStatus();
        // });

        // Check backup status every 10 seconds if backup is running
      //  setInterval(function() {
        //    checkBackupStatus();
       // }, 10000);
    });

    function checkBackupStatus() {
        $.ajax({
            url: '{{ route("backup.status") }}',
            type: 'GET',
            success: function(response) {
                if (response.is_running) {
                    $('#backupStatus').html('<span class="text-warning">جاري إنشاء النسخ الاحتياطي...</span>');
                    $('#createBackupBtn').prop('disabled', true);
                    $('#progressSection').removeClass('d-none');
                } else {
                    $('#backupStatus').html('<span class="text-success">جاهز</span>');
                    $('#createBackupBtn').prop('disabled', false);
                    $('#progressSection').addClass('d-none');
                }
                
                if (response.last_backup) {
                    $('#lastBackupTime').text(response.last_backup);
                }
            },
            error: function() {
                $('#backupStatus').html('<span class="text-danger">خطأ في التحقق من الحالة</span>');
            }
        });
    }

    function createBackup() {
        // Show progress bar
        $('#progressSection').removeClass('d-none');
        $('#createBackupBtn').prop('disabled', true);
        $('#backupResult').html('');

        $.ajax({
            url: '{{ route("backup.create") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#backupResult').html(`
                        <div class="alert alert-success">
                            <h4><i class="fas fa-check-circle"></i> تم إنشاء النسخ الاحتياطي بنجاح!</h4>
                            <p>${response.message}</p>
                        </div>
                    `);
                } else {
                    $('#backupResult').html(`
                        <div class="alert alert-danger">
                            <h4><i class="fas fa-exclamation-triangle"></i> فشل إنشاء النسخ الاحتياطي</h4>
                            <p>${response.message}</p>
                        </div>
                    `);
                }
                // checkBackupStatus();
            },
            error: function(xhr) {
                let message = 'حدث خطأ أثناء إنشاء النسخ الاحتياطي';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                $('#backupResult').html(`
                    <div class="alert alert-danger">
                        <h4><i class="fas fa-exclamation-triangle"></i> فشل إنشاء النسخ الاحتياطي</h4>
                        <p>${message}</p>
                    </div>
                `);
                // checkBackupStatus();
            }
        });
    }
    </script>
</body>
</html>