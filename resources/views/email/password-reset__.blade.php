<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>  Password Reset</title>
  </head>
  <style>
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
        /* margin-top: 20px; */
        /* display: block !important; */
      }
    }
    @media screen and (max-width: 730px) {
      .playstore {
        margin-left: 17% !important;
      }
      .ios {
        margin-left: 10px !important;
        /* margin-top: 20px; */
        /* display: block !important; */
      }
    }
    @media screen and (max-width: 700px) {
      .playstore {
        margin-left: 15% !important;
      }
      .ios {
        margin-left: 10px !important;
        /* margin-top: 20px; */
        /* display: block !important; */
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
            border-bottom: 5px solid #59b512;
          ">
          <div
            class="circle-holder"
            style="
              border-radius: 50%;
              width: 120px;
              height: 120px;
              border: 5px solid #59b512;
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
                background-color: #fecc00;
                background: linear-gradient(270deg, #675302, #ef562a);
                z-index: 1000;
                max-width: 95%;
                width: 1050px;
                margin-inline: auto;
                border-radius: 15px;
                text-align: center;
                padding-block: 10px;
                margin-top: 80px;
              ">
              <img
                src="{{asset('public/assets/mail/images/icons8-savouring-delicious-food-face-94.png')}}"
                alt="" />
              <h1
                style="
                  color: #e7e7e7;
                  font-family: Montserrat;
                  font-size: clamp(1rem, 7vw, 2rem);
                ">
                Password Reset 
              </h1>
              <p
                style="
                  padding-inline: 20px;
                  text-align: center;
                  font-size: 1rem;
                  line-height: 25px;
                  color: #e7e7e7;
                  font-family: Roboto, sans-serif;
                ">
                Dear {{$user['f_name']}}, <br />You requested a password reset for your {{env('APP_NAME')}} account.
                <br />
                Use the OTP below to reset your password.
              </p>
              <button
                style="
                  font-weight: 600;
                  background-color: #59b512;
                  border: none;
                  padding: 15px;
                  border-radius: 10px;
                  max-width: 45%;
                  width: 250px;
                  margin-top: 1rem;
                  margin-bottom: 1rem;
                  cursor: pointer;
                ">
                <a
                  href="#"
                  style="
                    text-decoration: none;
                    color: #f6f6f6;

                    font-family: Roboto, sans-serif;
                    font-size: 1rem;
                  "
                  >{{$token}}</a
                >
              </button>
            </div>
            <div
              style="
                background-color: #fecc00;
                background: linear-gradient(270deg, #675302, #ef562a);
                z-index: 1000;
                max-width: 95%;
                width: 800px;
                min-height: 200px;
                margin-inline: auto;
                margin-top: 80px;
                border-radius: 15px;
                text-align: center;
                padding-block: 15px;
              "
              class="clearfix">
              <div
                style="
                  width: 80px;
                  height: 80px;
                  border: 2px solid white;
                  border-radius: 50%;
                  display: block;
                  margin: 0 auto;
                ">
                <img
                  src="{{asset('public/assets/mail/images/hb_logo.png')}}"
                  alt=""
                  style="width: 100%; height: 100%; border-radius: 50%" />
              </div>
              <div
                style="display: block; margin-block: 20px; margin-left: 40%"
                class="social">
                <a href="" style="float: left">
                  <img
                    src="{{asset('public/assets/mail/images/facebook_4494479.png')}}"
                    alt=""
                    style="height: 40px; width: 40px" /><img src="" alt="" />
                </a>
                <a href="" style="float: left">
                  <img
                    style="height: 40px; width: 40px; margin-inline: 8px"
                    src="{{asset('public/assets/mail/images/instagram_3955024.png')}}"
                    alt="" />
                </a>
                <a href="" style="float: left">
                  <img
                    style="
                      height: 40px;
                      width: 40px;
                      border-radius: 50%;
                      background-color: #212121;
                    "
                    src="{{asset('public/assets/mail/images/icons8-twitterx-144.png')}}"
                    alt="" />
                </a>
              </div>
              <span
                style="
                  margin-block: 1rem;
                  font-size: 0.8rem;
                  color: #e7e7e7;
                  font-family: Montserrat;
                ">
                For Support contact us via
                <a href="">support@happybelle.com</a> <br />
              </span>
              <div
                style="margin-block: 15px; display: block; margin-left: 20%"
                class="clearfix playstore">
                <a
                  href=""
                  class="clearfix"
                  style="text-decoration: none; color: black; float: left">
                  <div
                    style="
                      background-color: #f6f6f6;
                      border-radius: 10px;
                      padding: 8px;
                    "
                    class="clearfix">
                    <img
                      src="{{asset('public/assets/mail/images/playstore_270011.png')}}"
                      alt=""
                      style="height: 35px; width: 35px; float: left" />
                    <h3
                      style="
                        font-family: Montserrat;
                        font-size: 0.8rem;
                        font-weight: 600;
                        float: left;
                        margin-left: 6px;
                      ">
                      Download from play store
                    </h3>
                  </div>
                </a>
                <a
                  href=""
                  class="ios"
                  style="
                    text-decoration: none;
                    color: black;
                    float: left;
                    margin-left: 20px;
                  ">
                  <div
                    style="
                      background-color: #f6f6f6;
                      border-radius: 10px;
                      padding: 8px;
                    "
                    class="clearfix">
                    <img
                      src="{{asset('public/assets/mail/images/icons8-apple-150.png')}}"
                      alt=""
                      style="height: 35px; width: 35px; float: left" />
                    <h3
                      style="
                        font-family: Montserrat;
                        font-size: 0.8rem;
                        font-weight: 600;
                        float: left;
                      ">
                      Download from apple store
                    </h3>
                  </div>
                </a>
              </div>
              <span
                style="
                  font-size: 0.8rem;
                  color: #e7e7e7;
                  font-family: Montserrat;
                  display: block;
                "
                >© Happy Belle, All rights reserved</span
              >
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
