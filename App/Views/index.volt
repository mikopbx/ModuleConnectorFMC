
<form class="ui large grey segment form" id="module-connectorfmc-form">
    {{ form.render('id') }}
    <div class="ui equal width aligned  grid">
        <div class="row">
          <div class="column">
            <h4>{{ t._('module_connectorfmc_TitleOutgoingFMC') }}</h4>
            <hr>
            <div class="field">
                <label >{{ t._('module_connectorfmc_outputEndpoint') }}</label>
                <div class="ui icon input">
                    {{ form.render('outputEndpoint') }}
                    <i class="circular sync alternate link icon"></i>
                </div>
            </div>
            <div class="field">
                <label >{{ t._('module_connectorfmc_outputEndpointSecret') }}</label>
                <div class="ui icon input">
                    {{ form.render('outputEndpointSecret') }}
                    <i class="circular sync alternate link icon"></i>
                </div>
            </div>
            <div class="ui message">
              <p> {{ t._('module_connectorfmc_TitleOutgoingMessage') }} </p>
            </div>
          </div>
          <div class="column">
             <h4>{{ t._('module_connectorfmc_TitleIncomingFMC') }}</h4>
             <hr>
             <div class="field disability">
                <label >{{ t._('module_connectorfmc_incomingEndpointHost') }}</label>
                    {{ form.render('incomingEndpointHost') }}
                </div>
                <div class="field disability">
                    <label >{{ t._('module_connectorfmc_incomingEndpointPort') }}</label>
                    {{ form.render('incomingEndpointPort') }}
                </div>
                <div class="field disability">
                    <label >{{ t._('module_connectorfmc_incomingEndpointLogin') }}</label>
                    {{ form.render('incomingEndpointLogin') }}
                </div>
                <div class="field disability">
                    <label >{{ t._('module_connectorfmc_incomingEndpointSecret') }}</label>
                    {{ form.render('incomingEndpointSecret') }}
                </div>
          </div>
        </div>
    </div>
    <div class="ui toggle checkbox">
        <label for="useDelayedResponse">{{ t._('module_connectorfmc_useDelayedResponse') }}</label>
        {{ form.render('useDelayedResponse') }}
        <br>
    </div>
    <div class="field">
        <label >{{ t._('module_connectorfmc_extensions') }}</label>
        {{ form.render('extensions') }}
        {{ form.render('peers') }}
    </div>

    <br>
    <div class="ui styled fluid accordion">
      <div class="title">
        <i class="dropdown icon"></i>
        {{ t._('module_connectorfmc_FmcServerSettings') }}
      </div>
      <div class="content">
        <div class="ui form">
            <div class="inline fields">
                <div class="three wide field">
                  <label>{{ t._('module_connectorfmc_sipPort') }}</label>
                </div>
                <div class="two wide field">
                    {{ form.render('sipPort') }}
                </div>
            </div>
            <div class="inline fields">
                <div class="three wide field">
                  <label>{{ t._('module_connectorfmc_amiPort') }}</label>
                </div>
                <div class="two wide field">
                    {{ form.render('amiPort') }}
                </div>
            </div>
            <div class="inline fields">
                <div class="three wide field">
                    <label>{{ t._('module_connectorfmc_rtpPorts') }}</label>
                </div>
                <div class="two wide field">
                    {{ form.render('rtpPortStart') }}
                </div>
                <div class="field">
                    <i class="minus icon"></i>
                </div>
                <div class="two wide field">
                    {{ form.render('rtpPortEnd') }}
                </div>
            </div>
        </div>
      </div>
    </div>
    <br>
    {{ partial("partials/submitbutton",['indexurl':'pbx-extension-modules/index/']) }}
</form>