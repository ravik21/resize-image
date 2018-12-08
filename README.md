# A script through Laravel Command for resizing images
Script for resizing the image as per aspect ratio

For this, we need add some required columns as per the requirement!

## Add following columns to you model containing images

public function up()
{
    Schema::table('images', function (Blueprint $table) {
        $table->string('s3_thumbnail_path')->nullable();
        $table->string('s3_mobile_path')->nullable();
        $table->string('s3_web_path')->nullable();
        $table->integer('compressed');
    });
}

/**
 * Reverse the migrations.
 *
 * @return void
 */
public function down()
{
  Schema::table('images', function (Blueprint $table) {
        $table->dropcolumn('s3_thumbnail_path');
        $table->dropcolumn('s3_mobile_path');
        $table->dropcolumn('s3_web_path');
        $table->dropcolumn('compressed');
  });
}
