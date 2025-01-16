<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>  Account Verification</title>
  </head>
  <style>
    @font-face {
      font-family: "Nourd";
      font-style: normal;
      font-weight: normal;
      src: url({{asset('public/assets/mail/font/nourd/nourd_medium.ttf')}}) format("woff2");
    }
    .clearfix ::after {
      content: "";
      clear: both;
      display: table;
    }

    @media screen and (max-width: 767px) {
      .playstore {
        margin-left: 18% !important;
        width: 100%;
      }
      .ios {
        margin-left: 10px !important;
      }
    }
    @media screen and (max-width: 730px) {
      .playstore {
        margin-left: 17% !important;
      }
      .ios {
        margin-left: 10px !important;
      }
    }
    @media screen and (max-width: 700px) {
      .playstore {
        margin-left: 15% !important;
      }
      .ios {
        margin-left: 10px !important;
      }
    }
    @media screen and (max-width: 600px) {
      .playstore {
        margin-left: 9% !important;
      }
      .ios {
        margin-left: 10px !important;
      }
    }
    @media screen and (max-width: 576px) {
      .playstore {
        margin-left: 7% !important;
      }
    }
    @media screen and (max-width: 500px) {
      .playstore {
        margin-left: 25% !important;
      }
      .social {
        margin-left: 35% !important;
      }
      .ios {
        margin-left: 0px !important;
        margin-right: 20px;
        margin-top: 20px;
        display: block !important;
      }
    }
    @media screen and (max-width: 480px) {
      .social {
        margin-left: 35% !important;
      }
      .playstore {
        margin-left: 25% !important;
      }
    }
    @media screen and (max-width: 400px) {
      .social {
        margin-left: 32% !important;
      }
      .playstore {
        margin-left: 20% !important;
      }
      .ios {
        margin-left: 0px !important;
        margin-right: 50px;
        display: block !important;
      }
    }
    @media screen and (max-width: 375px) {
      .playstore {
        margin-left: 18% !important;
      }
    }
    @media screen and (max-width: 350px) {
      .social {
        margin-left: 30% !important;
      }
      .playstore {
        margin-left: 15% !important;
      }
    }
    @media screen and (max-width: 330px) {
      .playstore {
        margin-left: 12% !important;
      }
    }
  </style>
  <body
    style="
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      background-color: #384a3a;
    ">
    <div>
      <div>
        <div
          class="header"
          style="
            background-image: url({{asset('public/assets/mail/images/doodle_food_pattern_1.jpg')}});
            width: 100%;
            height: 150px;
            background-position: center;
            background-size: cover;
            border-bottom: 5px solid #384a3a;
          ">
          <div
            class="circle-holder"
            style="
              border-radius: 50%;
              width: 120px;
              height: 120px;
              border: 5px solid #fff;
              margin: 0 auto;
              z-index: 100;
              top: 50%;
              position: relative;
            ">
            <img
              src="{{asset('public/assets/mail/images/hb_yellow_logo.png')}}"
              alt=""
              style="width: 100%; height: 100%; border-radius: 50%" />
          </div>
        </div>

        <div style="width: 100%; min-height: 870px">
          <div>
            <div
              style="
                /* background-color: #00000079; */
                /* background: linear-gradient(270deg, #283429, #283429); */
                z-index: 1000;
                max-width: 95%;
                min-height: 300px;
                width: 1050px;
                margin-inline: auto;
                border-radius: 15px;
                text-align: center;
                padding-block: 40px;
                margin-top: 80px;
              ">
              <h3
                style="
                  color: #e7e7e7;
                  font-family: Nourd;
                  font-size: clamp(0.85rem, 5vw, 1.5rem);
                ">
                 
              </h3>
              <img
                src="{{asset('public/assets/mail/images/logo.png')}}"
                alt="" />
              <h1
                style="
                  color: #e7e7e7;
                  font-family: Nourd;
                  font-size: clamp(1rem, 7vw, 2rem);
                ">
                Account Verification
              </h1>
              <p
                style="
                  padding-inline: 20px;
                  text-align: center;
                  font-size: 1rem;
                  line-height: 25px;
                  color: #e7e7e7;
                  font-family: Nourd;
                  padding-bottom: 1rem !important;
                ">
                Thank you for choosing {{env('APP_NAME')}}.
                <br />
                Use the OTP below to verify your account.
              </p>
              <span
                style="
                  font-weight: 600;
                  background-color: #00000045;
                  border: none;
                  padding: 15px;
                  border-radius: 10px;
                  max-width: 45%;
                  width: 250px;

                  text-decoration: none;
                  color: #f6f6f6;
                  letter-spacing: 20px;
                  font-family: Nourd;
                  font-size: 2rem;
                  text-align: center;
                ">
                {{$token}}
              </span>
            </div>

            <div
              style="
                background-color: #00000079;
                background: linear-gradient(270deg, #283429, #283429);
                z-index: 1000;
                max-width: 95%;
                width: 800px;
                min-height: 200px;
                margin-inline: auto;
                border-radius: 15px;
                text-align: center;
                border-radius: 35px;
                padding: 2% 3%;
                margin-top: 5%;
                text-align: center;
              ">
              <img
                src="{{asset('public/assets/mail/images/hb_logo.png')}}"
                style="
                  display: block;
                  margin-left: auto;
                  margin-right: auto;
                  margin-bottom: 3%;
                  width: 10%;
                "
                alt="" />

              <p
                style="
                  text-align: center;
                  font-family: Nourd;
                  font-weight: 100;
                  color: #e7e7e7;
                ">
                For support, contact us via
                <a href="mailto:support@ .com" style="color: #c9ac20">support@eat .com</a>
              </p>
              <p
                style="
                  text-align: center;
                  font-family: Nourd;
                  font-weight: 100;
                  color: #e7e7e7;
                ">
                Thank you for choosing us. Best regards,
              </p>

              <p></p>

              <p
                style="
                  text-align: center;
                  font-family: Nourd;
                  font-weight: 200;
                  color: #e7e7e7;
                ">
                ©
                <script>
                  document.write(new Date().getFullYear());
                </script>
                <strong style="letter-spacing: 5px">  .</strong> All
                rights reserved.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
